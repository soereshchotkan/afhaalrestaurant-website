<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'price',
        'special_instructions'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer'
    ];

    /**
     * Get the user that owns the cart item
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product associated with the cart item
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calculate the subtotal for this cart item
     */
    public function getSubtotalAttribute(): float
    {
        return $this->quantity * $this->price;
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return '€' . number_format($this->price, 2, ',', '.');
    }

    /**
     * Get formatted subtotal
     */
    public function getFormattedSubtotalAttribute(): string
    {
        return '€' . number_format($this->subtotal, 2, ',', '.');
    }
}