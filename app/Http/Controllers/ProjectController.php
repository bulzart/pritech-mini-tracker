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
     * Paginated list of projects with their issue counts. withCount avoids an
     * N+1 query when rendering the count column.
     */
    public function index(): View
    {
        $projects = Project::query()
            ->withCount('issues')
            ->latest()
            ->paginate(10);

        return view('projects.index', ['projects' => $projects]);
    }

    public function create(): View
    {
        return view('projects.create', ['project' => new Project]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        // validated() returns only the whitelisted fields, so mass assignment
        // is constrained to name/description/start_date/deadline.
        $project = Project::create($request->validated());

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Project created.');
    }

    public function show(Project $project): View
    {
        // Eager-load issues so the show view never triggers a per-issue query.
        $project->load('issues');

        return view('projects.show', ['project' => $project]);
    }

    public function edit(Project $project): View
    {
        return view('projects.edit', ['project' => $project]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        // Issues, their comments, and pivot rows are removed by the database
        // cascade defined in the migrations.
        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project deleted.');
    }
}
