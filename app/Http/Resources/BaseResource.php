<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseResource extends JsonResource
{
    protected $status = 200;
    protected $message = 'Success';

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'status' => $this->status ?? null,
                'message' => $this->message ?? null,
            ],
        ];
    }

    public function setStatusCode(int $statusCode): static
    {
        $this->status = $statusCode;

        return $this;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
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
