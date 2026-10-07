<?php

declare(strict_types=1);

namespace Modules\Vehicle\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleInspectionItem extends Model
{
    protected $fillable = [
        'inspection_id',
        'category',
        'item_name',
        'status',
        'remarks',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(VehicleInspection::class, 'inspection_id');
    }
}
