<?php

declare(strict_types=1);

namespace Modules\ApprovingOfficer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovingOfficerSignatory extends Model
{
    protected $fillable = ['approving_officer_id', 'name', 'title', 'show_on_ticket', 'ticket_order', 'sort_order'];

    protected $casts = ['show_on_ticket' => 'boolean', 'ticket_order' => 'integer'];

    public function officer(): BelongsTo
    {
        return $this->belongsTo(ApprovingOfficer::class, 'approving_officer_id');
    }
}
