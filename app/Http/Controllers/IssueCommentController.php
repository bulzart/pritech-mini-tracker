<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Issue;
use Illuminate\Http\JsonResponse;

/**
 * JSON endpoints powering the AJAX comments thread on the issue detail page.
 * The list is paginated so the page never loads an unbounded comment set in
 * one request (see ~/.claude/rules/enterprise/performance.md).
 */
final class IssueCommentController extends Controller
{
    /**
     * Comments per page. Small enough that a typical seeded issue spans more
     * than one page, so pagination is demonstrable out of the box.
     */
    private const int PER_PAGE = 5;

    /**
     * Paginated comments for an issue, newest first. The id tiebreaker keeps
     * ordering deterministic when several comments share a timestamp (e.g.
     * seeded data created in the same instant).
     */
    public function index(Issue $issue): JsonResponse
    {
        $comments = $issue->comments()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE);

        return CommentResource::collection($comments)->response();
    }

    /**
     * Create a comment under the route issue. issue_id is taken from the bound
     * route model, never from request input, so the comment cannot be attached
     * to an arbitrary issue (CWE-915 mass assignment).
     */
    public function store(StoreCommentRequest $request, Issue $issue): JsonResponse
    {
        $comment = $issue->comments()->create($request->validated());

        return CommentResource::make($comment)
            ->response()
            ->setStatusCode(201);
    }
}
