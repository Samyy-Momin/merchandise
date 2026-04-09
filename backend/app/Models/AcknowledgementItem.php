<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcknowledgementItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'acknowledgement_id', 'order_item_id', 'received_qty', 'status', 'comment',
    ];

    public function acknowledgement()
    {
        return $this->belongsTo(Acknowledgement::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}

