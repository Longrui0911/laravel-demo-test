<?php

namespace App\Http\Resources;
use App\Http\Resources\BaseResource as JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'slug' => $this->slug,
            'image' => $this->image,
            'imageUrl' => $this->imageUrl,
            'category_id' => $this->category_id,
            'upload_successful' => $this->upload_successful,
            'disk' => $this->disk,
            'project_id' => $this->project_id,
            'custom_fields' => $this->custom_fields,
            'amount' => $this->amount
        ];
    }
}
