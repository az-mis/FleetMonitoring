<?php

declare(strict_types=1);

namespace Modules\Vehicle\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class VehiclePmsItem extends Model
{
    protected $fillable = [
        'vehicle_id',
        'item_name',
        'category',
        'interval_km',
        'interval_months',
        'last_done_km',
        'last_done_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'last_done_date' => 'date',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Compute PMS status for this item based on current odometer and date.
     * Returns: 'overdue' | 'due_soon' | 'ok' | 'unknown'
     */
    public function getStatusAttribute(): string
    {
        $vehicle = $this->vehicle;
        $currentOdo = $vehicle?->odometer ?? 0;
        $today = Carbon::today();

        $kmOverdue = false;
        $kmDueSoon = false;
        $dateOverdue = false;
        $dateDueSoon = false;

        if ($this->interval_km && $this->last_done_km !== null) {
            $nextDueKm = $this->last_done_km + $this->interval_km;
            $diff = $nextDueKm - $currentOdo;
            if ($diff <= 0) $kmOverdue = true;
            elseif ($diff <= 500) $kmDueSoon = true;
        }

        if ($this->interval_months && $this->last_done_date) {
            $nextDueDate = Carbon::parse($this->last_done_date)->addMonths($this->interval_months);
            $daysDiff = $today->diffInDays($nextDueDate, false);
            if ($daysDiff < 0) $dateOverdue = true;
            elseif ($daysDiff <= 14) $dateDueSoon = true;
        }

        if ($kmOverdue || $dateOverdue) return 'overdue';
        if ($kmDueSoon || $dateDueSoon) return 'due_soon';
        if ($this->last_done_km !== null || $this->last_done_date) return 'ok';
        return 'unknown';
    }

    /**
     * Human-readable status label with details.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'overdue'  => 'Overdue',
            'due_soon' => 'Due Soon',
            'ok'       => 'On Track',
            default    => 'Not Started',
        };
    }

    /**
     * Next due km (or null if not applicable).
     */
    public function getNextDueKmAttribute(): ?int
    {
        if ($this->interval_km && $this->last_done_km !== null) {
            return $this->last_done_km + $this->interval_km;
        }
        return null;
    }

    /**
     * Next due date (or null if not applicable).
     */
    public function getNextDueDateAttribute(): ?Carbon
    {
        if ($this->interval_months && $this->last_done_date) {
            return Carbon::parse($this->last_done_date)->addMonths($this->interval_months);
        }
        return null;
    }

    protected $appends = ['status', 'status_label', 'next_due_km', 'next_due_date'];
}
