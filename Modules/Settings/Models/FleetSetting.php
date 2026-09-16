<?php

declare(strict_types=1);

namespace Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;

class FleetSetting extends Model
{
    protected $fillable = ['setting_key', 'setting_value', 'updated_by'];

    protected function casts(): array
    {
        return ['setting_value' => 'array'];
    }
}
