<?php

declare(strict_types=1);

namespace Modules\Maintenance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Vehicle\Models\Vehicle;

/** A purchase request (PR) for vehicle work, as tracked in the GSS "PR of Goods and Services" sheet. */
class Procurement extends Model
{
    public const STATUSES = ['PR on process', 'SVP', 'For payment', 'Reimbursement', 'Completed', 'Cancelled'];

    /** Work is done but the supplier or the employee who paid is still waiting for payment */
    public const AWAITING_PAYMENT = ['For payment', 'Reimbursement'];

    public const CATEGORIES = ['Vehicle', 'Vehicle parts', 'Vehicle tires', 'Vehicle PMS'];

    protected $fillable = [
        'vehicle_maintenance_id', 'vehicle_id', 'location', 'description', 'category', 'pr_number', 'pr_date',
        'amount', 'status', 'jo_po_number', 'jo_po_date', 'coa_date', 'remarks', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'pr_date' => 'date',
            'jo_po_date' => 'date',
            'coa_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(VehicleMaintenance::class, 'vehicle_maintenance_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeAwaitingPayment(Builder $query): void
    {
        $query->whereIn('status', self::AWAITING_PAYMENT);
    }

    /** Badge colours for the status: done, waiting on payment, in progress, or cancelled */
    public function tone(): string
    {
        return match (true) {
            $this->status === 'Completed' => 'success',
            in_array($this->status, self::AWAITING_PAYMENT, true) => 'warning',
            $this->status === 'Cancelled' => 'neutral',
            default => 'info',
        };
    }
}
