<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

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

    // Voeg deze methodes toe aan je User model:

    /**
     * Get the cart items for the user
     */
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get cart items with product details
     */
    public function cart()
    {
        return $this->cartItems()->with('product');
    }

    /**
     * Get cart total
     */
    public function getCartTotalAttribute(): float
    {
        return $this->cartItems->sum(function ($item) {
            return $item->quantity * $item->price;
        });
    }

    /**
     * Get cart item count
     */
    public function getCartCountAttribute(): int
    {
        return $this->cartItems->sum('quantity');
    }

    /**
     * Check if product is in cart
     */
    public function hasProductInCart($productId): bool
    {
        return $this->cartItems()->where('product_id', $productId)->exists();
    }

    /**
     * Clear the cart
     */
    public function clearCart(): void
    {
        $this->cartItems()->delete();
    }

}
