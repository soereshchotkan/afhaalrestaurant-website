<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'order_number',
        'subtotal',          // ← Deze miste
        'tax_amount',        // ← Deze miste  
        'total_amount',
        'status',
        'payment_method',
        'payment_status',
        'pickup_time',
        'notes',
        'customer_name',
        'customer_email',
        'customer_phone',
        // 'customer_address' // ← Deze staat niet in je migration
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',    // ← Toegevoegd
        'tax_amount' => 'decimal:2',  // ← Toegevoegd
        'total_amount' => 'decimal:2',
        'pickup_time' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Helper methods
    public static function generateOrderNumber(): string
    {
        do {
            // Format: ORD-YYYYMMDD-XXXX (bijv. ORD-20241125-0001)
            $date = now()->format('Ymd');
            $dailyCount = self::whereDate('created_at', today())->count() + 1;
            $orderNumber = 'ORD-' . $date . '-' . str_pad($dailyCount, 4, '0', STR_PAD_LEFT);
        } while (self::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    // Status check methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isPreparing(): bool
    {
        return $this->status === 'preparing';
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }
}