<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;

/**
 * JSON endpoints that attach/detach a tag to an issue from the issue detail
 * page via fetch(). State-changing, so CSRF protection (the X-CSRF-TOKEN
 * header) applies — see resources/js/issue-show.js.
 *
 * Both actions are idempotent: attaching an already-attached tag or detaching
 * a missing one is a clean no-op, never an error. This keeps the UI robust
 * against double-clicks and stale page state.
 */
final class IssueTagController extends Controller
{
    public function store(Issue $issue, Tag $tag): JsonResponse
    {
        // syncWithoutDetaching never creates a duplicate pivot row (the unique
        // index on issue_tag(issue_id, tag_id) also guarantees this).
        $issue->tags()->syncWithoutDetaching([$tag->id]);

        return response()->json([
            'attached' => true,
            'tag' => $this->tagPayload($tag),
            'message' => 'Tag attached.',
        ]);
    }

    public function destroy(Issue $issue, Tag $tag): JsonResponse
    {
        // detach() returns 0 when the tag was not attached — no error.
        $issue->tags()->detach($tag->id);

        return response()->json([
            'attached' => false,
            'tag' => $this->tagPayload($tag),
            'message' => 'Tag detached.',
        ]);
    }

    /**
     * @return array{id: int, name: string, color: string|null}
     */
    private function tagPayload(Tag $tag): array
    {
        return [
            'id' => $tag->id,
            'name' => $tag->name,
            'color' => $tag->color,
        ];
    }
}
