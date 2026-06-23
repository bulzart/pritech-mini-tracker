<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * JSON endpoints that assign/unassign a user to an issue from the issue detail
 * page via fetch(). State-changing, so CSRF protection (the X-CSRF-TOKEN
 * header) applies — see public/js/issue-show.js. Only the owner of the issue's
 * project may manage assignments (IssuePolicy::assign), and the route sits
 * behind the auth middleware, so the endpoints are never publicly accessible.
 *
 * Both actions are idempotent: assigning an already-assigned user or
 * unassigning a missing one is a clean no-op, never an error. This keeps the UI
 * robust against double-clicks and stale page state.
 */
final class IssueUserController extends Controller
{
    public function store(Issue $issue, User $user): JsonResponse
    {
        $this->authorize('assign', $issue);

        // syncWithoutDetaching never creates a duplicate pivot row (the unique
        // index on issue_user(issue_id, user_id) also guarantees this).
        $issue->assignees()->syncWithoutDetaching([$user->id]);

        return response()->json([
            'assigned' => true,
            'user' => $this->userPayload($user),
            'message' => 'User assigned.',
        ]);
    }

    public function destroy(Issue $issue, User $user): JsonResponse
    {
        $this->authorize('assign', $issue);

        // detach() returns 0 when the user was not assigned — no error.
        $issue->assignees()->detach($user->id);

        return response()->json([
            'assigned' => false,
            'user' => $this->userPayload($user),
            'message' => 'User unassigned.',
        ]);
    }

    /**
     * @return array{id: int, name: string, email: string}
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
