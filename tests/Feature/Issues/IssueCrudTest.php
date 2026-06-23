<?php

declare(strict_types=1);

namespace Tests\Feature\Issues;

use App\Models\Issue;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class IssueCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Every application route now requires authentication; sign in a user so
        // these feature tests exercise the routes as an authenticated request.
        $this->actingAs(User::factory()->create());
    }

    /** #1 */
    public function test_issues_index_returns_ok(): void
    {
        $this->get(route('issues.index'))->assertOk();
    }

    /** #2 */
    public function test_issue_index_displays_issues(): void
    {
        $project = Project::factory()->create();
        Issue::factory()->for($project)->create(['title' => 'Distinct Issue Title']);

        $this->get(route('issues.index'))
            ->assertOk()
            ->assertSee('Distinct Issue Title');
    }

    /** #3 */
    public function test_issue_index_displays_project_names(): void
    {
        $project = Project::factory()->create(['name' => 'Recognisable Project']);
        Issue::factory()->for($project)->create();

        $this->get(route('issues.index'))
            ->assertOk()
            ->assertSee('Recognisable Project');
    }

    /** #4 */
    public function test_issue_index_displays_tags(): void
    {
        $project = Project::factory()->create();
        $issue = Issue::factory()->for($project)->create();
        $tag = Tag::factory()->create(['name' => 'index-visible-tag']);
        $issue->tags()->attach($tag);

        $this->get(route('issues.index'))
            ->assertOk()
            ->assertSee('index-visible-tag');
    }

    /** #5 */
    public function test_create_page_returns_ok(): void
    {
        Project::factory()->create();

        $this->get(route('issues.create'))->assertOk();
    }

    /** #6 */
    public function test_create_page_shows_project_dropdown(): void
    {
        $project = Project::factory()->create(['name' => 'Dropdown Project']);

        $this->get(route('issues.create'))
            ->assertOk()
            ->assertSee('name="project_id"', false)
            ->assertSee('Dropdown Project');
    }

    /** #7 */
    public function test_a_valid_issue_can_be_created(): void
    {
        $project = Project::factory()->create();

        $response = $this->post(route('issues.store'), [
            'project_id' => $project->id,
            'title' => 'Created In Test',
            'description' => 'A description.',
            'status' => 'open',
            'priority' => 'high',
            'due_date' => '2026-09-01',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('issues', [
            'project_id' => $project->id,
            'title' => 'Created In Test',
            'status' => 'open',
            'priority' => 'high',
        ]);
    }

    /** Regression: description is nullable; creating without one must not 500. */
    public function test_an_issue_can_be_created_without_a_description(): void
    {
        $project = Project::factory()->create();

        $response = $this->post(route('issues.store'), [
            'project_id' => $project->id,
            'title' => 'No Description Issue',
            'status' => 'open',
            'priority' => 'low',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors(['description']);
        $this->assertDatabaseHas('issues', [
            'title' => 'No Description Issue',
            'description' => null,
        ]);
    }

    /** #8 */
    public function test_create_page_preselects_project_from_query_string(): void
    {
        $project = Project::factory()->create(['name' => 'Preselected Project']);

        $this->get(route('issues.create', ['project_id' => $project->id]))
            ->assertOk()
            // The matching option carries the selected attribute.
            ->assertSee('value="'.$project->id.'" selected', false);
    }

    /** #9 */
    public function test_an_invalid_project_id_fails_validation(): void
    {
        $response = $this->from(route('issues.create'))->post(route('issues.store'), [
            'project_id' => 999999,
            'title' => 'Bad Project',
            'status' => 'open',
            'priority' => 'low',
        ]);

        $response->assertSessionHasErrors('project_id');
        $this->assertDatabaseCount('issues', 0);
    }

    /** #10 */
    public function test_an_invalid_status_fails_validation(): void
    {
        $project = Project::factory()->create();

        $response = $this->from(route('issues.create'))->post(route('issues.store'), [
            'project_id' => $project->id,
            'title' => 'Bad Status',
            'status' => 'archived',
            'priority' => 'low',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertDatabaseCount('issues', 0);
    }

    /** #11 */
    public function test_an_invalid_priority_fails_validation(): void
    {
        $project = Project::factory()->create();

        $response = $this->from(route('issues.create'))->post(route('issues.store'), [
            'project_id' => $project->id,
            'title' => 'Bad Priority',
            'status' => 'open',
            'priority' => 'critical',
        ]);

        $response->assertSessionHasErrors('priority');
        $this->assertDatabaseCount('issues', 0);
    }

    /** #12 */
    public function test_a_missing_title_fails_validation(): void
    {
        $project = Project::factory()->create();

        $response = $this->from(route('issues.create'))->post(route('issues.store'), [
            'project_id' => $project->id,
            'title' => '',
            'status' => 'open',
            'priority' => 'low',
        ]);

        $response->assertSessionHasErrors('title');
        $this->assertDatabaseCount('issues', 0);
    }

    /** #13 */
    public function test_issue_detail_page_loads(): void
    {
        $issue = Issue::factory()->for(Project::factory())->create(['title' => 'Detail Title']);

        $this->get(route('issues.show', $issue))
            ->assertOk()
            ->assertSee('Detail Title');
    }

    /** #14 */
    public function test_issue_detail_page_displays_project(): void
    {
        $project = Project::factory()->create(['name' => 'Detail Project']);
        $issue = Issue::factory()->for($project)->create();

        $this->get(route('issues.show', $issue))
            ->assertOk()
            ->assertSee('Detail Project');
    }

    /** #15 */
    public function test_issue_detail_page_displays_tags(): void
    {
        $issue = Issue::factory()->for(Project::factory())->create();
        $tag = Tag::factory()->create(['name' => 'detail-tag']);
        $issue->tags()->attach($tag);

        $this->get(route('issues.show', $issue))
            ->assertOk()
            ->assertSee('detail-tag');
    }

    /** #16 */
    public function test_an_issue_can_be_updated(): void
    {
        $project = Project::factory()->create();
        $issue = Issue::factory()->for($project)->create(['title' => 'Old Title', 'status' => 'open']);

        $response = $this->patch(route('issues.update', $issue), [
            'project_id' => $project->id,
            'title' => 'Updated Title',
            'status' => 'closed',
            'priority' => 'medium',
        ]);

        $response->assertRedirect(route('issues.show', $issue));
        $this->assertDatabaseHas('issues', [
            'id' => $issue->id,
            'title' => 'Updated Title',
            'status' => 'closed',
        ]);
    }

    /** #17 */
    public function test_an_issue_can_be_deleted(): void
    {
        $issue = Issue::factory()->for(Project::factory())->create();

        $this->delete(route('issues.destroy', $issue))
            ->assertRedirect(route('issues.index'));

        $this->assertDatabaseMissing('issues', ['id' => $issue->id]);
    }

    /** #24 */
    public function test_project_show_page_links_to_issue_detail(): void
    {
        $project = Project::factory()->create();
        $issue = Issue::factory()->for($project)->create();

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee(route('issues.show', $issue), false);
    }

    /** #25 */
    public function test_project_show_page_has_create_issue_link_with_project_id(): void
    {
        $project = Project::factory()->create();

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee(route('issues.create', ['project_id' => $project->id]), false);
    }
}
