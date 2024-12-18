<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $thumbnail = $this->images()->thumbnail()->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'link' => route('products.detail', ['slug' => $this->slug]),
            'price' => $this->base_price,
            'price_discount' => $this->base_price_discount,
            'thumbnail' => $thumbnail ? asset('storage/uploads/product-images/'.$thumbnail->file_name) : null,
        ];
    }
}
