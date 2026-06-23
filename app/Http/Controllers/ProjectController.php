<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class ProjectController extends Controller
{
    /**
     * Every action is authorized against the ProjectPolicy with a per-action
     * $this->authorize() call. authorizeResource() is intentionally not used:
     * on Laravel 11+ it relies on an instance middleware() method that
     * controllers no longer provide. The auth middleware in routes/web.php
     * redirects guests to login first, so the policy always receives an
     * authenticated user (non-owners get a 403 from the update/delete checks).
     */
    public function index(): View
    {
        $this->authorize('viewAny', Project::class);

        $projects = Project::query()
            ->with('owner')
            ->withCount('issues')
            ->latest()
            ->paginate(10);

        return view('projects.index', ['projects' => $projects]);
    }

    public function create(): View
    {
        $this->authorize('create', Project::class);

        return view('projects.create', ['project' => new Project]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $this->authorize('create', Project::class);

        // Create through the owner relationship so user_id is set from the
        // authenticated user, never from request input — the project belongs to
        // its creator. validated() still constrains the writable columns to
        // name/description/start_date/deadline (CWE-915 mass assignment).
        $project = $request->user()->projects()->create($request->validated());

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Project created.');
    }

    public function show(Project $project): View
    {
        $this->authorize('view', $project);

        // Eager-load owner + issues so the show view never triggers a per-row
        // query.
        $project->load(['owner', 'issues']);

        return view('projects.show', ['project' => $project]);
    }

    public function edit(Project $project): View
    {
        $this->authorize('update', $project);

        return view('projects.edit', ['project' => $project]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        // Issues, their comments, and pivot rows are removed by the database
        // cascade defined in the migrations.
        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project deleted.');
    }
}
