<?php

namespace Modules\FleetCard\Services;

use Illuminate\Support\Facades\DB;
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

        $costPerKm = $card->cost_per_km;
        $consumed = $finalDistance * $costPerKm;
        $newBalance = max(0, $card->current_balance - $consumed);

        return [
            'distance_source' => $distanceSource,
            'distance_km' => $finalDistance,
            'cost_per_km' => $costPerKm,
            'consumed_amount' => $consumed,
            'current_balance' => $card->current_balance,
            'new_balance' => $newBalance,
        ];
    }

    /**
     * Deduct trip expense and log transaction
     */
    public function recordExpense(FleetCard $card, array $data): FleetCardLog
    {
        return DB::transaction(function () use ($card, $data) {
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
                    $dest = Destination::where('name', 'LIKE', '%' . $vReq->destination . '%')->first();
                    if ($dest && $dest->distance_km > 0) {
                        $finalDistance = (float) $dest->distance_km;
                        $distanceSource = 'destination_master';
                    }
                }
            }

            $costPerKm = $card->cost_per_km;
            $consumedAmount = round($finalDistance * $costPerKm, 2);

            $balanceBefore = (float) $card->current_balance;
            $balanceAfter = $balanceBefore - $consumedAmount;

            // Update card balance
            $card->current_balance = $balanceAfter;
            $card->updated_by = auth()->id();
            $card->save();

            // Create Log
            return FleetCardLog::create([
                'fleet_card_id' => $card->id,
                'vehicle_id' => $card->vehicle_id,
                'vehicle_request_id' => $vehicleRequestId,
                'distance_source' => $distanceSource,
                'category' => $data['category'] ?? 'fuel',
                'start_odometer' => $startOdo,
                'end_odometer' => $endOdo,
                'distance_km' => $finalDistance,
                'rate_peso_per_km' => $costPerKm,
                'consumed_amount' => $consumedAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'remarks' => $data['remarks'] ?? 'Trip expense deduction',
                'logged_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Top-up / Recharge fleet card budget
     */
    public function rechargeCard(FleetCard $card, float $amount, ?string $remarks = null): FleetCardLog
    {
        return DB::transaction(function () use ($card, $amount, $remarks) {
            $balanceBefore = (float) $card->current_balance;
            $balanceAfter = $balanceBefore + $amount;

            $card->current_balance = $balanceAfter;
            $card->initial_budget = max((float) $card->initial_budget, $balanceAfter);
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

            $topUpAmount = abs((float) $log->consumed_amount);
            $newBalance = max(0, (float) $card->current_balance - $topUpAmount);

            // If initial budget was pushed up by top up, adjust it down or back to base
            $newBudget = max($newBalance, (float) $card->initial_budget - $topUpAmount);

            $card->current_balance = $newBalance;
            $card->initial_budget = $newBudget;
            $card->updated_by = auth()->id();
            $card->save();

            $log->delete();
        });
    }
}
