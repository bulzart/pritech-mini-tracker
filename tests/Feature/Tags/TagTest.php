<?php

declare(strict_types=1);

namespace Tests\Feature\Tags;

use App\Models\Issue;
use App\Models\Project;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TagTest extends TestCase
{
    use RefreshDatabase;

    /** #26 */
    public function test_tags_index_returns_ok(): void
    {
        $this->get(route('tags.index'))->assertOk();
    }

    /** #27 */
    public function test_tag_index_displays_tags(): void
    {
        Tag::factory()->create(['name' => 'visible-tag']);

        $this->get(route('tags.index'))
            ->assertOk()
            ->assertSee('visible-tag');
    }

    /** #28 */
    public function test_tag_create_page_returns_ok(): void
    {
        $this->get(route('tags.create'))->assertOk();
    }

    /** #29 */
    public function test_a_valid_tag_can_be_created(): void
    {
        $response = $this->post(route('tags.store'), [
            'name' => 'new-tag',
            'color' => '#2563eb',
        ]);

        $response->assertRedirect(route('tags.index'));
        $this->assertDatabaseHas('tags', ['name' => 'new-tag', 'color' => '#2563eb']);
    }

    /** #30 */
    public function test_a_duplicate_tag_name_fails_validation(): void
    {
        Tag::factory()->create(['name' => 'duplicate']);

        $response = $this->from(route('tags.create'))
            ->post(route('tags.store'), ['name' => 'duplicate']);

        $response->assertRedirect(route('tags.create'));
        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('tags', 1);
    }

    /** #31 */
    public function test_tag_color_can_be_nullable(): void
    {
        $response = $this->post(route('tags.store'), ['name' => 'colorless']);

        $response->assertSessionDoesntHaveErrors(['color']);
        $this->assertDatabaseHas('tags', ['name' => 'colorless', 'color' => null]);
    }

    /** #32 */
    public function test_tag_index_displays_issue_count(): void
    {
        $tag = Tag::factory()->create(['name' => 'counted-tag']);
        $project = Project::factory()->create();
        // Attach the tag to a distinctive number of issues.
        $issues = Issue::factory()->count(7)->for($project)->create();
        $tag->issues()->attach($issues->pluck('id'));

        $this->get(route('tags.index'))
            ->assertOk()
            ->assertSee('counted-tag')
            ->assertSee('7');
    }
}
