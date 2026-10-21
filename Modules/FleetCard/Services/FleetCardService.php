<?php

namespace Modules\FleetCard\Services;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Destination\Models\Destination;
use Modules\FleetCard\Models\FleetCard;
use Modules\FleetCard\Models\FleetCardLog;
use Modules\VehicleRequest\Models\VehicleRequest;

class FleetCardService
{
    /**
     * Calculate expenses and remaining balances for previewing
     */
    public function previewCalculation(FleetCard $card, ?float $distanceKm = null, ?int $startOdometer = null, ?int $endOdometer = null): array
    {
        $distanceSource = 'manual';
        $finalDistance = 0.0;

        if ($startOdometer !== null && $endOdometer !== null && $endOdometer > $startOdometer) {
            $finalDistance = (float) ($endOdometer - $startOdometer);
            $distanceSource = 'odometer';
        } elseif ($distanceKm !== null && $distanceKm > 0) {
            $finalDistance = (float) $distanceKm;
            $distanceSource = 'manual';
        }

        // Same pricing as recordExpense: km ÷ the vehicle's km per litre × price per litre
        $costPerKm = $card->pricePerKm();
        $consumed = round($finalDistance * ($costPerKm ?? 0), 2);

        return [
            'distance_source' => $distanceSource,
            'distance_km' => $finalDistance,
            'cost_per_km' => $costPerKm ?? 0,
            'needs_fuel_economy' => $finalDistance > 0 && $costPerKm === null,
            'consumed_amount' => $consumed,
            'current_balance' => $card->current_balance,
            'new_balance' => (float) $card->current_balance - $consumed,
        ];
    }

    /**
     * Deduct trip expense and log transaction
     */
    public function recordExpense(FleetCard $card, array $data): FleetCardLog
    {
        return DB::transaction(function () use ($card, $data) {
            // Lock the card row so concurrent deductions cannot overwrite each other's balance
            $card = FleetCard::whereKey($card->id)->lockForUpdate()->firstOrFail();
            $distanceSource = $data['distance_source'] ?? 'manual';
            $finalDistance = (float) ($data['distance_km'] ?? 0);
            $startOdo = isset($data['start_odometer']) ? (int) $data['start_odometer'] : null;
            $endOdo = isset($data['end_odometer']) ? (int) $data['end_odometer'] : null;
            $vehicleRequestId = $data['vehicle_request_id'] ?? null;

            // Auto distance detection logic
            if ($startOdo !== null && $endOdo !== null && $endOdo > $startOdo) {
                $finalDistance = (float) ($endOdo - $startOdo);
                $distanceSource = 'odometer';
            } elseif ($vehicleRequestId) {
                $vReq = VehicleRequest::find($vehicleRequestId);
                if ($vReq && $vReq->distance_km > 0) {
                    $finalDistance = (float) $vReq->distance_km;
                    $distanceSource = 'destination_master';
                } elseif ($vReq && $vReq->destination) {
                    // The destination is free text from the public form; escape LIKE wildcards
                    $escapedDestination = addcslashes((string) $vReq->destination, '\\%_');
                    $dest = Destination::where('location', $card->location)
                        ->where('name', 'LIKE', '%' . $escapedDestination . '%')
                        ->first();
                    if ($dest && $dest->distance_km > 0) {
                        $finalDistance = (float) $dest->distance_km;
                        $distanceSource = 'destination_master';
                    }
                }
            }

            $fuelPricePerLiter = isset($data['fuel_price_per_liter']) && (float) $data['fuel_price_per_liter'] > 0
                ? (float) $data['fuel_price_per_liter']
                : (float) $card->rate_peso;

            $litersUsed = null;
            $trip = $vehicleRequestId ? VehicleRequest::with(['completion', 'vehicle'])->find($vehicleRequestId) : null;

            if ($distanceSource === 'destination_master' && $trip) {
                // Prioritize Part B completion actual distance travelled over estimated route distance
                if ($trip->completion && (float) $trip->completion->distance_travelled > 0) {
                    $finalDistance = (float) $trip->completion->distance_travelled;
                }
                // Litres from Part B are the most accurate charge
                $fuelUsed = (float) ($trip->completion?->fuel_used_liters ?? 0);
                if ($fuelUsed > 0) {
                    $litersUsed = $fuelUsed;
                }
            }

            // Distance only: the litres it took, from the vehicle's fuel economy (km ÷ km per litre)
            $vehicle = $trip?->vehicle ?? $card->vehicle;
            if ($litersUsed === null && $finalDistance > 0) {
                $kmPerLiter = (float) ($vehicle?->fuel_economy_km_per_liter ?? 0);
                if ($kmPerLiter <= 0) {
                    throw ValidationException::withMessages([
                        'distance_km' => 'Set the fuel economy (km per litre) of '.($vehicle?->plate_number ?? 'the card\'s vehicle')
                            .' on its Property & Registration page first, or charge the trip from its Part B litres.',
                    ]);
                }
                $litersUsed = round($finalDistance / $kmPerLiter, 2);
            }

            $consumedAmount = round(($litersUsed ?? 0) * $fuelPricePerLiter, 2);
            $savedFuelPrice = $litersUsed !== null ? $fuelPricePerLiter : null;
            $pricePerKm = $finalDistance > 0 ? round($consumedAmount / $finalDistance, 2) : 0.0;

            $balanceBefore = (float) $card->current_balance;
            $balanceAfter = $balanceBefore - $consumedAmount;

            // Update card balance
            $card->current_balance = $balanceAfter;
            $card->updated_by = auth()->id();
            $card->save();

            // Create Log (the database allows one charge per trip: a second quick click lands here)
            try {
                return FleetCardLog::create([
                    'fleet_card_id' => $card->id,
                    'vehicle_id' => $card->vehicle_id,
                    'vehicle_request_id' => $vehicleRequestId,
                    'distance_source' => $distanceSource,
                    'category' => $data['category'] ?? 'fuel',
                    'start_odometer' => $startOdo,
                    'end_odometer' => $endOdo,
                    'distance_km' => $finalDistance,
                    'liters_used' => $litersUsed,
                    'fuel_price_per_liter' => $savedFuelPrice,
                    'rate_peso_per_km' => $pricePerKm,
                    'consumed_amount' => $consumedAmount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'remarks' => $data['remarks'] ?? 'Trip expense deduction',
                    'logged_by' => auth()->id(),
                ]);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['vehicle_request_id' => 'This trip ticket has already been charged to a fleet card.']);
            }
        });
    }

    /**
     * Top-up / Recharge fleet card budget
     */
    public function rechargeCard(FleetCard $card, float $amount, ?string $remarks = null): FleetCardLog
    {
        return DB::transaction(function () use ($card, $amount, $remarks) {
            $card = FleetCard::whereKey($card->id)->lockForUpdate()->firstOrFail();
            $balanceBefore = (float) $card->current_balance;
            $balanceAfter = $balanceBefore + $amount;

            // The starting budget stays as set; top-ups are counted from their own log entries
            $card->current_balance = $balanceAfter;
            $card->updated_by = auth()->id();
            $card->save();

            return FleetCardLog::create([
                'fleet_card_id' => $card->id,
                'vehicle_id' => $card->vehicle_id,
                'distance_source' => 'recharge',
                'distance_km' => 0,
                'rate_peso_per_km' => 0,
                'consumed_amount' => -$amount, // Negative consumed = top-up
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'remarks' => $remarks ?: ('Budget Recharge (+₱' . number_format($amount, 2) . ')'),
                'logged_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Delete a top-up log and revert card balance
     */
    public function deleteRecharge(FleetCard $card, FleetCardLog $log): void
    {
        DB::transaction(function () use ($card, $log) {
            abort_unless($log->fleet_card_id === $card->id && $log->distance_source === 'recharge', 400);
            $card = FleetCard::whereKey($card->id)->lockForUpdate()->firstOrFail();
            // Re-read under the lock: a double click must not take the top-up back twice
            $log = FleetCardLog::whereKey($log->id)->lockForUpdate()->first();
            if (! $log) {
                return;
            }

            // Take back exactly this top-up. The balance may go below zero if it was already spent:
            // that is what the ledger says, and hiding it would make the card disagree with its logs.
            $card->current_balance = (float) $card->current_balance - abs((float) $log->consumed_amount);
            $card->updated_by = auth()->id();
            $card->save();

            $log->delete();
        });
    }
}
