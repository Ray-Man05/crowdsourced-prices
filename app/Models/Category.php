<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasTranslations;

class Category extends Model
{
    use HasTranslations;

    protected $fillable = ['name', 'color'];

    protected $casts = ['name' => 'array'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}