<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PriceEstimate extends Model
{
    use SoftDeletes;

    public const ESTIMATE_COOLDOWN_DAYS = 7;

    protected static function booted(): void
    {
        $bump = function (self $e): void {
            // Cache::increment silently no-ops on non-existent keys with the database
            // driver. Use an explicit read-modify-write so the version key is always
            // created or updated, which invalidates bulkCityMetrics cache entries.
            $key = "agg_v:{$e->product_id}";
            Cache::put($key, (int) Cache::get($key, 0) + 1, now()->addDays(30));
        };
        static::created($bump);
        static::updated($bump);
        static::deleted($bump);
    }

    protected $fillable = ['price', 'user_id', 'product_id', 'currency_id', 'city_id', 'recorded_at'];

    protected $casts = [
        'price' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Convert this estimate's price to a target currency.
     */
    public function convertTo(Currency $targetCurrency): ?float
    {
        return $this->currency->convert($this->price, $targetCurrency);
    }

    /**
     * Core averaging logic: convert a collection of estimates to a target
     * currency and return their mean. Estimates whose currency has no exchange
     * rate to the target are silently excluded (not counted in the denominator).
     *
     * @param  Collection<PriceEstimate>  $estimates
     */
    public static function convertAndAverage(
        Collection $estimates,
        Currency $targetCurrency,
    ): ?float {
        $total = 0.0;
        $count = 0;

        foreach ($estimates as $estimate) {
            $converted = $estimate->currency->convert($estimate->price, $targetCurrency);
            if ($converted !== null) {
                $total += $converted;
                $count++;
            }
        }

        return $count > 0 ? $total / $count : null;
    }

    /**
     * Scope to estimates within the past N days. 0 = no restriction.
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        if ($days > 0) {
            $query->where('recorded_at', '>=', Carbon::now()->subDays($days));
        }

        return $query;
    }

    /**
     * Whether a user is currently on cooldown for a given product.
     */
    public static function isOnCooldown(User $user, Product $product): bool
    {
        return self::withTrashed()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('recorded_at', '>=', Carbon::now()->subDays(self::ESTIMATE_COOLDOWN_DAYS))
            ->exists();
    }

    /**
     * The datetime when the user can next submit for this product.
     * Returns null if they are not on cooldown.
     */
    public static function cooldownEndsAt(User $user, Product $product): ?Carbon
    {
        $latest = self::withTrashed()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->latest('recorded_at')
            ->value('recorded_at');

        if (! $latest) {
            return null;
        }

        $endsAt = Carbon::parse($latest)->addDays(self::ESTIMATE_COOLDOWN_DAYS);

        return $endsAt->isFuture() ? $endsAt : null;
    }
}
