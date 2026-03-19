<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionCheckPhoto extends Model
{
    protected $fillable = ['inspection_check_id', 'path'];

    /** @return BelongsTo<InspectionCheck, $this> */
    public function inspectionCheck(): BelongsTo
    {
        return $this->belongsTo(InspectionCheck::class);
    }
}
