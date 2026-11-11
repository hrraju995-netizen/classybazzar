<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /** Frontend expects bare objects/arrays, not a "data" envelope. */
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reviewerName' => $this->reviewer_name,
            'rating' => (int) $this->rating,
            'comment' => $this->comment,
            'date' => $this->created_at?->toIso8601String(),
        ];
    }
}
