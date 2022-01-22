<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BaseResource extends JsonResource
{
    /**
     * @inheritDoc
     */
    public function with($request)
    {
        return [
            'code' => 200,
            'meta' => null,
        ];
    }
}
