<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Acknowledgement extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'user_id', 'status', 'comments',
        'employee_code', 'receiver_name', 'branch_manager_name', 'remarks', 'rating',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(AcknowledgementItem::class);
    }
}
