<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }

    public function toResponse($request)
    {
        $response = parent::toResponse($request);
        $data = json_decode($response->getContent(), true);

        // Deep clean entire JSON (including with() + toArray())
        $cleaned = $this->replaceNullWithString($data);

        return response()->json($cleaned, $response->getStatusCode());
    }

    protected function replaceNullWithString($data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->replaceNullWithString($value);
            } elseif (is_null($value)) {
                $data[$key] = '';
            }
        }

        return $data;
    }
}
