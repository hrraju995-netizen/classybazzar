<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderEvent extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [ 'order_id', 'status', 'title', 'description', 'happened_at' ];

    protected function casts(): array
    {
        return ['happened_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
