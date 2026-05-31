<?php

namespace App\Livewire\Concerns;

use App\Models\Product;
use App\Models\UserBasket;

/**
 * Shared basket logic for Livewire components.
 *
 * Requires the component to implement getActiveBasket().
 * Override afterItemAdded(), afterItemRemoved(), afterBasketEmptied() for component-specific behavior.
 */
trait HasBasket
{
    /** In-memory basket: [product_id => ['product_id', 'name', 'unit', 'quantity', 'category_color']] */
    public array $basket = [];

    abstract protected function getActiveBasket(): UserBasket;

    /**
     * Adds (or increments) an item in the DB basket and syncs $basket.
     */
    protected function addItem(int $productId, float $quantity): bool
    {
        $product = Product::with(['unit', 'category'])->find($productId);
        if (! $product) {
            return false;
        }

        $dbBasket = $this->getActiveBasket();
        $dbBasket->addItem($productId, $quantity);

        if (isset($this->basket[$productId])) {
            $this->basket[$productId]['quantity'] += $quantity;
        } else {
            $this->basket[$productId] = [
                'product_id' => $productId,
                'name' => $product->name,
                'unit' => $product->unit?->symbol ?? '',
                'quantity' => $quantity,
                'category_color' => $product->category?->color ?? '#ffffff',
            ];
        }

        $this->afterItemAdded($productId);

        return true;
    }

    /**
     * Removes an item from the DB basket and syncs $basket.
     */
    protected function removeItem(int $productId): void
    {
        $this->getActiveBasket()->removeItem($productId);
        unset($this->basket[$productId]);

        if (empty($this->basket)) {
            $this->afterBasketEmptied();
        } else {
            $this->afterItemRemoved($productId);
        }
    }

    /**
     * Loads the basket from DB into $basket (call in mount()).
     */
    protected function syncBasketFromDb(): void
    {
        $this->basket = $this->getActiveBasket()->toMapArray();
    }

    // ── Extension hooks ────────────────────────────────────────────────────

    protected function afterItemAdded(int $productId): void {}

    protected function afterItemRemoved(int $productId): void {}

    protected function afterBasketEmptied(): void {}
}
