<?php

namespace App\Traits;

trait ApiService
{
    public function responseJson(array $data = [], int $code = 200, int $status = 200, ?array $meta = null): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'code' => $code,
            'meta' => $meta,
            'data' => $data
        ], $status);
    }
}
