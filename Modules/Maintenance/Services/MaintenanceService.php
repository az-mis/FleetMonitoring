<?php

declare(strict_types=1);

namespace Modules\Maintenance\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Maintenance\Models\VehicleMaintenance;
use Modules\Vehicle\Models\Vehicle;

class MaintenanceService
{
    public function list(
        ?string $search = null,
        ?string $location = null,
        ?string $status = null,
        int $perPage = 10,
    ): LengthAwarePaginator {
        return $this->query($search, $location, $status)
            ->with(['vehicle.assignedDriver', 'procurements'])
            ->paginate($perPage)->withQueryString();
    }

    /** The page a record is on in the list with these filters, or null when the filters leave it out */
    public function pageOf(int $id, ?string $search, ?string $location, ?string $status, int $perPage): ?int
    {
        $position = $this->query($search, $location, $status)->pluck('id')->search($id);

        return $position === false ? null : intdiv($position, $perPage) + 1;
    }

    /** Whether the status filter (active, scheduled, awaiting payment…) shows this record */
    public function statusShows(VehicleMaintenance $record, string $status): bool
    {
        return $this->query(null, null, $status)->whereKey($record->id)->exists();
    }

    private function query(?string $search, ?string $location, ?string $status): \Illuminate\Database\Eloquent\Builder
    {
        // Newest first; the id keeps records on the same date in a fixed order, so a record's page is known
        $query = VehicleMaintenance::query()->orderBy('scheduled_date', 'desc')->orderBy('id', 'desc');

        if ($location) {
            $query->where('location', $location);
        }

        if ($status === 'active') {
            $query->whereIn('status', ['scheduled', 'ongoing']);
        } elseif ($status === 'awaiting_payment') {
            // Work done (or under way) whose purchase request still waits for payment
            $query->whereHas('procurements', fn ($q) => $q->awaitingPayment());
        } elseif ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('plate_number', 'like', "%{$search}%")
                  ->orWhere('vehicle_label', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function counts(?string $location = null): array
    {
        $query = VehicleMaintenance::query();
        if ($location) {
            $query->where('location', $location);
        }

        return [
            'active'    => (clone $query)->whereIn('status', ['scheduled', 'ongoing'])->count(),
            'scheduled' => (clone $query)->where('status', 'scheduled')->count(),
            'ongoing'   => (clone $query)->where('status', 'ongoing')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            'all'       => (clone $query)->count(),
            'awaiting_payment' => (clone $query)->whereHas('procurements', fn ($q) => $q->awaitingPayment())->count(),
        ];
    }

    public function create(array $data, Vehicle $vehicle): VehicleMaintenance
    {
        if (($data['status'] ?? '') === 'completed' && empty($data['completed_date'])) {
            $data['completed_date'] = now()->toDateString();
        }

        $record = VehicleMaintenance::create([
            ...$data,
            'plate_number'  => $vehicle->plate_number,
            'vehicle_label' => trim("{$vehicle->brand} {$vehicle->model}"),
            'location'      => $vehicle->location,
            'created_by'      => auth()->id(),
            'created_by_name' => auth()->user()?->name,
        ]);

        if ($record->status === 'completed') {
            $this->syncCompletedMaintenanceWithVehicleAndPms($record, $vehicle);
        }

        return $record;
    }

    public function update(VehicleMaintenance $record, array $data, Vehicle $vehicle): VehicleMaintenance
    {
        if (($data['status'] ?? '') === 'completed' && empty($data['completed_date'])) {
            $data['completed_date'] = now()->toDateString();
        }

        $record->update([
            ...$data,
            'plate_number'  => $vehicle->plate_number,
            'vehicle_label' => trim("{$vehicle->brand} {$vehicle->model}"),
            'location'      => $vehicle->location,
        ]);

        if ($record->status === 'completed') {
            $this->syncCompletedMaintenanceWithVehicleAndPms($record, $vehicle);
        }

        return $record;
    }

    /**
     * Bridges Maintenance completion into the Vehicle's oil status and PMS items.
     */
    private function syncCompletedMaintenanceWithVehicleAndPms(VehicleMaintenance $record, Vehicle $vehicle): void
    {
        $doneKm = $record->odometer ?? $vehicle->odometer;
        $doneDate = $record->completed_date ?? now()->toDateString();

        // 1. If maintenance type is Oil Change, sync vehicle change oil odometer
        if ($record->type === 'Oil Change') {
            $updateData = [];
            if ($doneKm && $doneKm > ($vehicle->last_change_oil_odometer ?? 0)) {
                $updateData['last_change_oil_odometer'] = $doneKm;
            }
            if ($doneKm && $doneKm > ($vehicle->odometer ?? 0)) {
                $updateData['odometer'] = $doneKm;
            }
            if (!empty($updateData)) {
                $vehicle->update($updateData);
            }
        }

        // 2. If a specific PMS item was linked to this maintenance, update it
        if ($record->pms_item_id && $pmsItem = $record->pmsItem) {
            $pmsItem->update([
                'last_done_km'   => $doneKm,
                'last_done_date' => $doneDate,
            ]);
        } else {
            // Find any matching PMS item by name or type
            $matchingPmsItem = $vehicle->pmsItems()
                ->where(function ($q) use ($record) {
                    $q->where('item_name', 'like', "%{$record->type}%")
                      ->orWhere('item_name', 'like', "%{$record->title}%");
                })->first();

            if ($matchingPmsItem) {
                $matchingPmsItem->update([
                    'last_done_km'   => $doneKm,
                    'last_done_date' => $doneDate,
                ]);
            }
        }
    }

    public function delete(VehicleMaintenance $record): void
    {
        $record->delete();
    }

    /** Returns active (scheduled/ongoing) records for a given vehicle, excluding one record by id. */
    public function activeRecordsForVehicle(int $vehicleId, ?int $excludeId = null): \Illuminate\Database\Eloquent\Collection
    {
        return VehicleMaintenance::where('vehicle_id', $vehicleId)
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->orderBy('scheduled_date')
            ->get();
    }
}
