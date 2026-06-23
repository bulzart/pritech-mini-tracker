<?php

declare(strict_types=1);

namespace Tests\Feature\Issues;

use App\Models\Issue;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class IssueSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Every application route requires authentication.
        $this->actingAs(User::factory()->create());
    }

    /**
     * GET the index as an XHR (X-Requested-With), which returns just the
     * results partial — the same path the debounced search JS uses.
     */
    private function ajax(string $url): TestResponse
    {
        return $this->get($url, ['X-Requested-With' => 'XMLHttpRequest']);
    }

    public function test_search_input_appears_on_the_index(): void
    {
        $this->get(route('issues.index'))
            ->assertOk()
            ->assertSee('name="search"', false)
            ->assertSee('Search issues...', false);
    }

    public function test_search_by_title_returns_matching_issues(): void
    {
        $project = Project::factory()->create();
        Issue::factory()->for($project)->create(['title' => 'Unique Searchable Phrase']);
        Issue::factory()->for($project)->create(['title' => 'Completely Different']);

        $this->get(route('issues.index', ['search' => 'Searchable']))
            ->assertOk()
            ->assertSee('Unique Searchable Phrase')
            ->assertDontSee('Completely Different');
    }

    public function test_search_by_description_returns_matching_issues(): void
    {
        $project = Project::factory()->create();
        Issue::factory()->for($project)->create([
            'title' => 'First Issue',
            'description' => 'Contains the word marmalade in the body.',
        ]);
        Issue::factory()->for($project)->create([
            'title' => 'Second Issue',
            'description' => 'Nothing relevant here.',
        ]);

        $this->get(route('issues.index', ['search' => 'marmalade']))
            ->assertOk()
            ->assertSee('First Issue')
            ->assertDontSee('Second Issue');
    }

    public function test_search_excludes_unrelated_issues(): void
    {
        $project = Project::factory()->create();
        Issue::factory()->for($project)->create(['title' => 'Alpha Match Here']);
        Issue::factory()->for($project)->create(['title' => 'Bravo Unrelated']);

        $this->get(route('issues.index', ['search' => 'Alpha']))
            ->assertOk()
            ->assertSee('Alpha Match Here')
            ->assertDontSee('Bravo Unrelated');
    }

    public function test_search_combines_with_the_status_filter(): void
    {
        $project = Project::factory()->create();
        Issue::factory()->for($project)->create(['title' => 'Searchterm Open', 'status' => 'open']);
        Issue::factory()->for($project)->create(['title' => 'Searchterm Closed', 'status' => 'closed']);

        $this->get(route('issues.index', ['search' => 'Searchterm', 'status' => 'open']))
            ->assertOk()
            ->assertSee('Searchterm Open')
            ->assertDontSee('Searchterm Closed');
    }

    public function test_search_combines_with_the_priority_filter(): void
    {
        $project = Project::factory()->create();
        Issue::factory()->for($project)->create(['title' => 'Searchterm High', 'priority' => 'high']);
        Issue::factory()->for($project)->create(['title' => 'Searchterm Low', 'priority' => 'low']);

        $this->get(route('issues.index', ['search' => 'Searchterm', 'priority' => 'high']))
            ->assertOk()
            ->assertSee('Searchterm High')
            ->assertDontSee('Searchterm Low');
    }

    public function test_search_combines_with_the_tag_filter(): void
    {
        $project = Project::factory()->create();
        $matching = Issue::factory()->for($project)->create(['title' => 'Searchterm Tagged']);
        $other = Issue::factory()->for($project)->create(['title' => 'Searchterm Untagged']);

        $matching->tags()->attach(Tag::factory()->create(['name' => 'backend']));
        $other->tags()->attach(Tag::factory()->create(['name' => 'frontend']));

        $this->get(route('issues.index', ['search' => 'Searchterm', 'tag' => 'backend']))
            ->assertOk()
            ->assertSee('Searchterm Tagged')
            ->assertDontSee('Searchterm Untagged');
    }

    public function test_empty_search_returns_the_normal_list(): void
    {
        $project = Project::factory()->create();
        Issue::factory()->for($project)->create(['title' => 'Visible One']);
        Issue::factory()->for($project)->create(['title' => 'Visible Two']);

        $this->get(route('issues.index', ['search' => '']))
            ->assertOk()
            ->assertSee('Visible One')
            ->assertSee('Visible Two');
    }

    public function test_ajax_search_returns_only_the_results_partial(): void
    {
        $project = Project::factory()->create();
        Issue::factory()->for($project)->create(['title' => 'Ajax Match']);
        Issue::factory()->for($project)->create(['title' => 'Ajax Miss']);

        $this->ajax(route('issues.index', ['search' => 'Match']))
            ->assertOk()
            ->assertSee('Ajax Match')
            ->assertDontSee('Ajax Miss')
            // The partial is just the results region, not the full page layout.
            ->assertDontSee('Mini Issue Tracker')
            ->assertDontSee('data-issues-filters', false);
    }

    public function test_pagination_preserves_the_search_query(): void
    {
        $project = Project::factory()->create();
        // More than one page (15 per page) of matching issues.
        Issue::factory()->count(20)->for($project)->create(['title' => 'Common Search Token']);

        $this->get(route('issues.index', ['search' => 'Common']))
            ->assertOk()
            ->assertSee('search=Common', false)
            ->assertSee('page=2', false);

        $this->get(route('issues.index', ['search' => 'Common', 'page' => 2]))
            ->assertOk();
    }

    public function test_search_and_all_filters_work_together(): void
    {
        $project = Project::factory()->create();
        $match = Issue::factory()->for($project)->create([
            'title' => 'Quadruple Match', 'status' => 'open', 'priority' => 'high',
        ]);
        Issue::factory()->for($project)->create([
            'title' => 'Quadruple Wrong Status', 'status' => 'closed', 'priority' => 'high',
        ]);

        $match->tags()->attach(Tag::factory()->create(['name' => 'backend']));

        $this->get(route('issues.index', [
            'search' => 'Quadruple',
            'status' => 'open',
            'priority' => 'high',
            'tag' => 'backend',
        ]))
            ->assertOk()
            ->assertSee('Quadruple Match')
            ->assertDontSee('Quadruple Wrong Status');
    }
}
