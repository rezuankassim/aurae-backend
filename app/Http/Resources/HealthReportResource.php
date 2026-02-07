<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class HealthReportResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_body_file' => $this->full_body_file,
            'full_body_file_url' => $this->full_body_file ? url('api/health-reports/'.$this->id.'/file/full_body') : null,
            'meridian_file' => $this->meridian_file,
            'meridian_file_url' => $this->meridian_file ? url('api/health-reports/'.$this->id.'/file/meridian') : null,
            'multidimensional_file' => $this->multidimensional_file,
            'multidimensional_file_url' => $this->multidimensional_file ? url('api/health-reports/'.$this->id.'/file/multidimensional') : null,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
