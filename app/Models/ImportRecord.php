<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One row of a GSS workbook import: what it became, and whether GSS should check it */
class ImportRecord extends Model
{
    protected $fillable = ['source', 'sheet', 'row', 'key', 'record_type', 'record_id', 'outcome', 'needs_review', 'reason', 'raw'];

    protected function casts(): array
    {
        return ['needs_review' => 'boolean', 'raw' => 'array', 'row' => 'integer'];
    }
}
