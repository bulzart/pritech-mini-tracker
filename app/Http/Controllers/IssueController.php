<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreIssueRequest;
use App\Http\Requests\UpdateIssueRequest;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class IssueController extends Controller
{
    /**
     * Issues per page on the index. Query strings (filters) are preserved
     * across pages via withQueryString().
     */
    private const int PER_PAGE = 15;

    /**
     * Searchable, filterable, paginated list of issues. The search/status/
     * priority/tag scopes each treat null/empty input as a no-op, so an absent
     * term or filter never breaks the query. project and tags are eager-loaded
     * to keep the Blade loop free of N+1 queries. An XHR gets just the results
     * partial for in-place swap; a normal request gets the full page.
     */
    public function index(Request $request): View|Response
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'priority' => $request->string('priority')->toString(),
            'tag' => $request->string('tag')->toString(),
        ];

        $issues = Issue::query()
            ->with(['project', 'tags'])
            ->search($filters['search'])
            ->status($filters['status'])
            ->priority($filters['priority'])
            ->tag($filters['tag'])
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        // The debounced search/filter fetches over XHR and swaps only the
        // results region; a normal request renders the full page (progressive
        // enhancement — the GET form still works with JavaScript disabled).
        if ($request->ajax()) {
            return response()->view('issues._results', [
                'issues' => $issues,
                'filters' => $filters,
            ]);
        }

        return view('issues.index', [
            'issues' => $issues,
            'projects' => Project::query()->orderBy('name')->get(),
            'tags' => Tag::query()->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    /**
     * The create form. A project_id query parameter (used by the "New issue"
     * link on a project page) preselects that project in the dropdown.
     */
    public function create(Request $request): View
    {
        return view('issues.create', [
            'issue' => new Issue(['status' => 'open', 'priority' => 'medium']),
            'projects' => Project::query()->orderBy('name')->get(),
            'selectedProjectId' => $request->integer('project_id') ?: null,
        ]);
    }

    public function store(StoreIssueRequest $request): RedirectResponse
    {
        $issue = Issue::create($request->validated());

        return redirect()
            ->route('issues.show', $issue)
            ->with('success', 'Issue created.');
    }

    /**
     * Issue detail. project and tags are eager-loaded; comments are loaded
     * separately through the paginated AJAX endpoint, not here.
     */
    public function show(Request $request, Issue $issue): View
    {
        $issue->load(['project', 'tags', 'assignees']);

        // Tags not yet attached, offered for AJAX attach.
        $availableTags = Tag::query()
            ->whereNotIn('id', $issue->tags->pluck('id'))
            ->orderBy('name')
            ->get();

        // Users not yet assigned, offered for AJAX assignment. Only the owner of
        // the issue's project (canAssign) sees the assign/unassign controls.
        $availableUsers = User::query()
            ->whereNotIn('id', $issue->assignees->pluck('id'))
            ->orderBy('name')
            ->get();

        return view('issues.show', [
            'issue' => $issue,
            'availableTags' => $availableTags,
            'availableUsers' => $availableUsers,
            'canAssign' => $request->user()->can('assign', $issue),
        ]);
    }

    public function edit(Issue $issue): View
    {
        return view('issues.edit', [
            'issue' => $issue,
            'projects' => Project::query()->orderBy('name')->get(),
            'selectedProjectId' => $issue->project_id,
        ]);
    }

    public function update(UpdateIssueRequest $request, Issue $issue): RedirectResponse
    {
        $issue->update($request->validated());

        return redirect()
            ->route('issues.show', $issue)
            ->with('success', 'Issue updated.');
    }

    public function destroy(Issue $issue): RedirectResponse
    {
        // Comments and pivot rows are removed by the database cascade defined
        // in the migrations.
        $issue->delete();

        return redirect()
            ->route('issues.index')
            ->with('success', 'Issue deleted.');
    }
}
