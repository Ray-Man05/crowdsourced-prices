<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasTranslations;
use Illuminate\Support\Collection;

class Category extends Model
{
    use HasTranslations;

    protected $fillable = ['name', 'color'];

    protected $casts = ['name' => 'array'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public static function withSortedProducts(): Collection
    {
        return self::with(['products' => function ($query) {
                $query->with('unit')->orderBy('name');
            }])
            ->orderBy('name')
            ->get();
    }
}
