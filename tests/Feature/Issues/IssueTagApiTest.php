<?php

declare(strict_types=1);

namespace Tests\Feature\Issues;

use App\Models\Issue;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class IssueTagApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Every application route now requires authentication; sign in a user so
        // these feature tests exercise the routes as an authenticated request.
        $this->actingAs(User::factory()->create());
    }

    private function issue(): Issue
    {
        return Issue::factory()->for(Project::factory())->create();
    }

    /** #33 */
    public function test_attaching_a_tag_persists_the_pivot_row(): void
    {
        $issue = $this->issue();
        $tag = Tag::factory()->create();

        $this->postJson(route('issues.tags.attach', [$issue, $tag]))->assertOk();

        $this->assertDatabaseHas('issue_tag', [
            'issue_id' => $issue->id,
            'tag_id' => $tag->id,
        ]);
    }

    /** #34 */
    public function test_attach_endpoint_returns_json(): void
    {
        $issue = $this->issue();
        $tag = Tag::factory()->create(['name' => 'json-tag']);

        $this->postJson(route('issues.tags.attach', [$issue, $tag]))
            ->assertOk()
            ->assertJson([
                'attached' => true,
                'tag' => ['id' => $tag->id, 'name' => 'json-tag'],
            ])
            ->assertJsonStructure(['attached', 'message', 'tag' => ['id', 'name', 'color']]);
    }

    /** #35 */
    public function test_duplicate_attach_does_not_create_a_duplicate_pivot_row(): void
    {
        $issue = $this->issue();
        $tag = Tag::factory()->create();

        $this->postJson(route('issues.tags.attach', [$issue, $tag]))->assertOk();
        $this->postJson(route('issues.tags.attach', [$issue, $tag]))->assertOk();

        $this->assertDatabaseCount('issue_tag', 1);
    }

    /** #36 */
    public function test_detaching_a_tag_removes_the_pivot_row(): void
    {
        $issue = $this->issue();
        $tag = Tag::factory()->create();
        $issue->tags()->attach($tag);

        $this->deleteJson(route('issues.tags.detach', [$issue, $tag]))
            ->assertOk()
            ->assertJson(['attached' => false]);

        $this->assertDatabaseMissing('issue_tag', [
            'issue_id' => $issue->id,
            'tag_id' => $tag->id,
        ]);
    }

    /** #37 */
    public function test_detaching_a_missing_attachment_does_not_crash(): void
    {
        $issue = $this->issue();
        $tag = Tag::factory()->create();

        // Never attached — detaching must still succeed cleanly.
        $this->deleteJson(route('issues.tags.detach', [$issue, $tag]))
            ->assertOk()
            ->assertJson(['attached' => false]);

        $this->assertDatabaseCount('issue_tag', 0);
    }

    /** #38 */
    public function test_attaching_to_an_invalid_issue_returns_not_found(): void
    {
        $tag = Tag::factory()->create();

        $this->postJson(route('issues.tags.attach', [999999, $tag->id]))
            ->assertNotFound();
    }

    /** #39 */
    public function test_attaching_an_invalid_tag_returns_not_found(): void
    {
        $issue = $this->issue();

        $this->postJson(route('issues.tags.attach', [$issue->id, 999999]))
            ->assertNotFound();
    }
}
