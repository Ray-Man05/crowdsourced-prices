<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = ['name', 'iso_code', 'currency_id'];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}