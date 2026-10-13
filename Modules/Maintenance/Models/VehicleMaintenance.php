<?php

declare(strict_types=1);

namespace Modules\Maintenance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Vehicle\Models\Vehicle;
use App\Models\User;

class VehicleMaintenance extends Model
{
    protected $fillable = [
        'vehicle_id',
        'plate_number',
        'vehicle_label',
        'type',
        'title',
        'description',
        'status',
        'priority',
        'scheduled_date',
        'scheduled_date_end',
        'completed_date',
        'odometer',
        'trigger_km',
        'cost',
        'vendor',
        'notes',
        'location',
        'created_by',
        'created_by_name',
        'source_report_id',
        'pms_item_id',
        'pms_template_id',
    ];

    protected $casts = [
        'scheduled_date'     => 'date',
        'scheduled_date_end' => 'date',
        'completed_date'     => 'date',
        'cost'               => 'decimal:2',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function pmsItem(): BelongsTo
    {
        return $this->belongsTo(\Modules\Vehicle\Models\VehiclePmsItem::class, 'pms_item_id');
    }

    public function pmsTemplate(): BelongsTo
    {
        return $this->belongsTo(\Modules\Vehicle\Models\VehiclePmsTemplate::class, 'pms_template_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Returns trigger status based on current vehicle odometer vs trigger_km.
     * 'overdue' | 'due_soon' | 'ok' | null (if no trigger_km set)
     */
    public function getTriggerStatusAttribute(): ?string
    {
        if (!$this->trigger_km) return null;
        $odo = $this->vehicle?->odometer ?? 0;
        $diff = $this->trigger_km - $odo;
        if ($diff <= 0) return 'overdue';
        if ($diff <= 500) return 'due_soon';
        return 'ok';
    }
}
