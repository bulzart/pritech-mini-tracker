<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/**
 * Project authorization.
 *
 * Documented rule (CHECKPOINT.md): any authenticated user may list, view, and
 * create projects; only the owner (project.user_id === user.id) may update or
 * delete one. Deny-by-default — every method returns an explicit boolean and
 * the auth middleware guarantees an authenticated $user (OWASP A01 Broken
 * Access Control). Discovered automatically by Laravel's policy auto-discovery
 * (App\Models\Project → App\Policies\ProjectPolicy).
 */
final class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }
}
