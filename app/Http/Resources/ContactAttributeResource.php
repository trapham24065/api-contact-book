<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactAttributeResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'attribute_id' => $this->attribute_id,
            'attr_key'     => $this->attr_key,
            'attr_value'   => $this->attr_value,
        ];
    }

}
