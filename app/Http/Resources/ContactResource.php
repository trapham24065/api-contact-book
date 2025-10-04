<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'contact_id' => $this->contact_id,
            'name'       => $this->name,
            'phone'      => $this->phone,
            'email'      => $this->email,
            'note'       => $this->note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'attributes' => ContactAttributeResource::collection($this->whenLoaded('attributes')),
        ];
    }

}
