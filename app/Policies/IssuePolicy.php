<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Issue;
use App\Models\User;

/**
 * Issue authorization. In this checkpoint only assignment management is gated:
 * a user may assign/unassign members on an issue only if they own the issue's
 * project (documented rule — CHECKPOINT.md). Deny-by-default; the auth
 * middleware guarantees an authenticated $user (OWASP A01 Broken Access
 * Control). Registered in AppServiceProvider (and via Laravel auto-discovery).
 */
final class IssuePolicy
{
    public function assign(User $user, Issue $issue): bool
    {
        return $user->id === $issue->project->user_id;
    }
}
