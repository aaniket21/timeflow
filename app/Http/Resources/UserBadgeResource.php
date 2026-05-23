<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserBadgeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'badge' => new BadgeResource($this->whenLoaded('badge')),
            'unlocked_at' => $this->unlocked_at?->toIso8601String(),
        ];
    }
}
