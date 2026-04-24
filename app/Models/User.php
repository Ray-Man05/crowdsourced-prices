<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', 
        'locale', 
        'theme',
        'city_id', 
        'currency_id',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function priceEstimates(): HasMany
    {
        return $this->hasMany(PriceEstimate::class);
    }

    public function effectiveCurrency(): ?Currency
    {
        return $this->currency
            ?? $this->city?->country?->currency
            ?? null;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function latestEstimateFor(Product $product): ?PriceEstimate
    {
        return $this->priceEstimates()
            ->where('product_id', $product->id)
            ->latest('recorded_at')
            ->first();
    }

    public function canEstimate(Product $product): bool
    {
        return !PriceEstimate::isOnCooldown($this, $product);
    }

    public function cooldownEndsAt(Product $product): ?Carbon
    {
        return PriceEstimate::cooldownEndsAt($this, $product);
    }
}
