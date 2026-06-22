<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public JSON shape for a comment. Deliberately narrow — only the fields the
 * issue detail page renders. created_at is emitted in ISO 8601 for machines
 * and a human-friendly relative string for direct display.
 *
 * @mixin Comment
 */
final class CommentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'author_name' => $this->author_name,
            'body' => $this->body,
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_for_humans' => $this->created_at?->diffForHumans(),
        ];
    }
}
