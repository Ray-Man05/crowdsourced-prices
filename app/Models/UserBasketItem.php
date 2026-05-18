<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBasketItem extends Model
{
    protected $fillable = ['user_basket_id', 'product_id', 'quantity'];

    protected $casts = ['quantity' => 'float'];

    public function basket(): BelongsTo
    {
        return $this->belongsTo(UserBasket::class, 'user_basket_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
