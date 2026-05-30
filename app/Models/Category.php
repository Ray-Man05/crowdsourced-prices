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
        // orderBy('name') on a JSON column sorts by the raw JSON string, not the translated
        // value, producing non-deterministic results. JSON_UNQUOTE(JSON_EXTRACT(..., '$.en'))
        // extracts and sorts by the English name, consistent with ProductCatalog's JSON search.
        return self::with(['products' => function ($query) {
                $query->with('unit')
                      ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))");
            }])
            ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))")
            ->get();
    }
}
