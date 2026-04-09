<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'price', 'category', 'category_id', 'image_url', 'stock',
    ];

    protected $casts = [
        'price' => 'float',
        'stock' => 'integer',
    ];

    // Use a non-conflicting relation name since a legacy 'category' column exists
    public function categoryRelation()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
