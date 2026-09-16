<?php

declare(strict_types=1);

namespace Modules\ApprovingOfficer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovingOfficer extends Model
{
    protected $fillable = ['location', 'signee1_name', 'signee1_title', 'signee2_name', 'signee2_title', 'updated_by'];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}
