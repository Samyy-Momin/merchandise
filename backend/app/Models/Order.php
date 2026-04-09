<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'status', 'total_amount', 'address_id',
    ];

    protected $casts = [
        'total_amount' => 'float',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function approvals()
    {
        return $this->hasMany(Approval::class);
    }

    public function shipment()
    {
        return $this->hasOne(Shipment::class);
    }

    public function acknowledgements()
    {
        return $this->hasMany(Acknowledgement::class);
    }

    public function issues()
    {
        return $this->hasMany(Issue::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
}
