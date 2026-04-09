<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $category = $this->whenLoaded('categoryRelation');
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => (float) $this->price,
            'image_url' => $this->image_url,
            'category' => $category ? [
                'id' => $category->id,
                'name' => $category->name,
            ] : null,
        ];
    }
}

