<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_number',
        'courier',
        'courier_consignment_id',
        'courier_response',
        'user_id',
        'customer_name',
        'phone',
        'email',
        'address',
        'shipping_area',
        'subtotal',
        'shipping_charge',
        'discount',
        'total',
        'coupon_code',
        'payment_method',
        'payment_status',
        'payment_number',
        'transaction_id',
        'status',
        'carrier',
        'tracking_code',
        'estimated_delivery_at',
        'note',
    ];

    public const STATUS_FLOW = [
        'pending',
        'confirmed',
        'processing',
        'in_transit',
        'out_for_delivery',
        'delivered',
    ];

    public const STATUS_LABELS = [
        'pending' => 'Order Placed',
        'confirmed' => 'Order Confirmed',
        'processing' => 'Packed',
        'in_transit' => 'In Transit',
        'out_for_delivery' => 'Out for Delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'returned' => 'Returned',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'shipping_charge' => 'integer',
            'discount' => 'integer',
            'total' => 'integer',
            'estimated_delivery_at' => 'date',
            'courier_response' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->orderBy('happened_at');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'], true);
    }
}
