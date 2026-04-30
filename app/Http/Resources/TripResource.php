<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TripResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->image) {
            $file = Storage::disk('private')->get($this->image);
            $mime = Storage::disk('private')->mimeType($this->image);
        }

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'destination' => $this->destination,
            'image' => $this->image ? 'data:' . $mime . ';base64,' . base64_encode($file) : null,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'budget' => $this->budget,
            'activities' => ActivityResource::collection($this->whenLoaded('activities')),
            'files' => TripFileResource::collection($this->whenLoaded('files')),
        ];
    }
}
