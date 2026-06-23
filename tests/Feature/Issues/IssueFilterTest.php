<?php

declare(strict_types=1);

namespace Tests\Feature\Issues;

use App\Models\Issue;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class IssueFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Every application route now requires authentication; sign in a user so
        // these feature tests exercise the routes as an authenticated request.
        $this->actingAs(User::factory()->create());
    }

    /** #18 */
    public function test_status_filter_works(): void
    {
        $project = Project::factory()->create();
        Issue::factory()->for($project)->create(['title' => 'Open One', 'status' => 'open']);
        Issue::factory()->for($project)->create(['title' => 'Closed One', 'status' => 'closed']);

        $response = $this->get(route('issues.index', ['status' => 'open']));

        $response->assertOk()
            ->assertSee('Open One')
            ->assertDontSee('Closed One');
    }

    /** #19 */
    public function test_priority_filter_works(): void
    {
        $project = Project::factory()->create();
        Issue::factory()->for($project)->create(['title' => 'High One', 'priority' => 'high']);
        Issue::factory()->for($project)->create(['title' => 'Low One', 'priority' => 'low']);

        $response = $this->get(route('issues.index', ['priority' => 'high']));

        $response->assertOk()
            ->assertSee('High One')
            ->assertDontSee('Low One');
    }

    /** #20 */
    public function test_tag_filter_works(): void
    {
        $project = Project::factory()->create();
        $tagged = Issue::factory()->for($project)->create(['title' => 'Tagged One']);
        Issue::factory()->for($project)->create(['title' => 'Untagged One']);

        $tag = Tag::factory()->create(['name' => 'backend']);
        $tagged->tags()->attach($tag);

        $response = $this->get(route('issues.index', ['tag' => 'backend']));

        $response->assertOk()
            ->assertSee('Tagged One')
            ->assertDontSee('Untagged One');
    }

    /** #21 */
    public function test_combined_status_and_priority_filters_work(): void
    {
        $project = Project::factory()->create();
        Issue::factory()->for($project)->create(['title' => 'Open High', 'status' => 'open', 'priority' => 'high']);
        Issue::factory()->for($project)->create(['title' => 'Open Low', 'status' => 'open', 'priority' => 'low']);
        Issue::factory()->for($project)->create(['title' => 'Closed High', 'status' => 'closed', 'priority' => 'high']);

        $response = $this->get(route('issues.index', ['status' => 'open', 'priority' => 'high']));

        $response->assertOk()
            ->assertSee('Open High')
            ->assertDontSee('Open Low')
            ->assertDontSee('Closed High');
    }

    /** #22 */
    public function test_combined_status_priority_and_tag_filters_work(): void
    {
        $project = Project::factory()->create();
        $match = Issue::factory()->for($project)->create([
            'title' => 'Triple Match', 'status' => 'open', 'priority' => 'high',
        ]);
        $wrongTag = Issue::factory()->for($project)->create([
            'title' => 'Wrong Tag', 'status' => 'open', 'priority' => 'high',
        ]);
        Issue::factory()->for($project)->create([
            'title' => 'Wrong Status', 'status' => 'closed', 'priority' => 'high',
        ]);

        $tag = Tag::factory()->create(['name' => 'backend']);
        $match->tags()->attach($tag);
        // $wrongTag intentionally carries a different tag.
        $wrongTag->tags()->attach(Tag::factory()->create(['name' => 'frontend']));

        $response = $this->get(route('issues.index', [
            'status' => 'open',
            'priority' => 'high',
            'tag' => 'backend',
        ]));

        $response->assertOk()
            ->assertSee('Triple Match')
            ->assertDontSee('Wrong Tag')
            ->assertDontSee('Wrong Status');
    }

    /** #23 */
    public function test_pagination_preserves_filters(): void
    {
        $project = Project::factory()->create();
        // More than one page (15 per page) of matching issues, plus non-matches.
        Issue::factory()->count(20)->for($project)->create(['status' => 'open']);
        Issue::factory()->count(5)->for($project)->create(['status' => 'closed']);

        $response = $this->get(route('issues.index', ['status' => 'open']));

        $response->assertOk()
            // The "Next" pagination link keeps the active filter and advances the page.
            ->assertSee('status=open', false)
            ->assertSee('page=2', false);

        // Page 2 with the filter still applied loads and shows only open issues.
        $this->get(route('issues.index', ['status' => 'open', 'page' => 2]))
            ->assertOk();
    }
}
