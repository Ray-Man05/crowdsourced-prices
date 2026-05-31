<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserBasket extends Model
{
    protected $fillable = ['user_id', 'name', 'type', 'color'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(UserBasketItem::class);
    }

    /**
     * Add or increment a product in the basket.
     */
    public function addItem(int $productId, float $quantity): UserBasketItem
    {
        $item = $this->items()->where('product_id', $productId)->first();

        if ($item) {
            $item->increment('quantity', $quantity);
            $item->refresh();

            return $item;
        }

        return $this->items()->create([
            'product_id' => $productId,
            'quantity' => $quantity,
        ]);
    }

    /**
     * Remove a product from the basket.
     */
    public function removeItem(int $productId): void
    {
        $this->items()->where('product_id', $productId)->delete();
    }

    /**
     * Returns the basket contents in the array format MapPage expects.
     * Keys are product IDs; each value has product_id, name, unit, quantity, category_color.
     */
    public function toMapArray(): array
    {
        $items = $this->items()->with(['product.unit', 'product.category'])->get();

        $result = [];
        foreach ($items as $item) {
            $product = $item->product;
            if (! $product) {
                continue;
            }

            $result[$product->id] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'unit' => $product->unit?->symbol ?? '',
                'quantity' => (float) $item->quantity,
                'category_color' => $product->category?->color ?? '#ffffff',
            ];
        }

        return $result;
    }
}
