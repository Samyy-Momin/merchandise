<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'vendor_id', 'tracking_number', 'courier_name', 'status', 'estimated_delivery_date',
    ];

    protected $casts = [
        'estimated_delivery_date' => 'date',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function logs()
    {
        return $this->hasMany(TrackingLog::class);
    }

    public function items()
    {
        return $this->hasMany(\App\Models\ShipmentItem::class);
    }
}
