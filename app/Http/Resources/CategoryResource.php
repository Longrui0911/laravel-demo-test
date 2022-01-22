<?php

namespace App\Http\Resources;

use App\Http\Resources\BaseResource as JsonResource;

class CategoryResource extends JsonResource
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
            'project_id' => $this->project_id
        ];
    }
}
