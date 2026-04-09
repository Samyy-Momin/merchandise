<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id', 'status', 'message',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}

