<?php

declare(strict_types=1);

namespace Tests\Feature\Projects;

use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProjectCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_projects_index(): void
    {
        // The root redirect itself is public; projects.index is gated.
        $this->get('/')->assertRedirect(route('projects.index'));
    }

    public function test_projects_index_returns_ok(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('projects.index'))->assertOk();
    }

    public function test_projects_index_displays_existing_projects(): void
    {
        $this->actingAs(User::factory()->create());
        $projects = Project::factory()->count(3)->create();

        $response = $this->get(route('projects.index'));

        $response->assertOk();

        foreach ($projects as $project) {
            $response->assertSee($project->name);
        }
    }

    public function test_create_page_returns_ok(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('projects.create'))->assertOk();
    }

    public function test_a_valid_project_can_be_created(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('projects.store'), [
            'name' => 'Alpha Project',
            'description' => 'A project created during testing.',
            'start_date' => '2026-01-01',
            'deadline' => '2026-02-01',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'name' => 'Alpha Project',
            'description' => 'A project created during testing.',
            'user_id' => $user->id,
        ]);
    }

    public function test_an_invalid_project_name_shows_a_validation_error(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->from(route('projects.create'))
            ->post(route('projects.store'), ['name' => '']);

        $response->assertRedirect(route('projects.create'));
        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('projects', 0);
    }

    public function test_a_deadline_before_the_start_date_fails_validation(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->from(route('projects.create'))
            ->post(route('projects.store'), [
                'name' => 'Bad Dates',
                'start_date' => '2026-01-10',
                'deadline' => '2026-01-05',
            ]);

        $response->assertSessionHasErrors('deadline');
        $this->assertDatabaseCount('projects', 0);
    }

    public function test_a_deadline_equal_to_the_start_date_passes_validation(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->post(route('projects.store'), [
            'name' => 'Equal Dates',
            'start_date' => '2026-01-10',
            'deadline' => '2026-01-10',
        ]);

        $response->assertSessionDoesntHaveErrors(['start_date', 'deadline']);
        $this->assertDatabaseHas('projects', ['name' => 'Equal Dates']);
    }

    public function test_nullable_start_date_and_deadline_are_accepted(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->post(route('projects.store'), ['name' => 'No Dates']);

        $response->assertSessionDoesntHaveErrors(['start_date', 'deadline']);
        $this->assertDatabaseHas('projects', [
            'name' => 'No Dates',
            'start_date' => null,
            'deadline' => null,
        ]);
    }

    public function test_a_project_can_be_viewed(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee($project->name);
    }

    public function test_project_show_displays_related_issues(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        Issue::factory()->for($project)->create(['title' => 'Visible Issue Title']);

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Visible Issue Title');
    }

    public function test_edit_page_returns_ok(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('projects.edit', $project))
            ->assertOk()
            ->assertSee($project->name);
    }

    public function test_a_project_can_be_updated(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);

        $response = $this->actingAs($user)->patch(route('projects.update', $project), [
            'name' => 'New Name',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'New Name',
        ]);
    }

    public function test_a_project_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'));
    }

    public function test_a_deleted_project_no_longer_exists(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->delete(route('projects.destroy', $project));

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    // --- Authorization / ownership (Part 2) ---

    public function test_guest_cannot_create_project(): void
    {
        $response = $this->post(route('projects.store'), ['name' => 'Guest Project']);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('projects', 0);
    }

    public function test_guest_cannot_view_the_edit_form(): void
    {
        $project = Project::factory()->create();

        $this->get(route('projects.edit', $project))
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_delete_project(): void
    {
        $project = Project::factory()->create();

        $this->delete(route('projects.destroy', $project))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_an_authenticated_user_can_create_a_project(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('projects.store'), ['name' => 'Authed Project'])
            ->assertRedirect();

        $this->assertDatabaseHas('projects', ['name' => 'Authed Project']);
    }

    public function test_a_created_project_belongs_to_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('projects.store'), ['name' => 'Owned By Creator']);

        $this->assertDatabaseHas('projects', [
            'name' => 'Owned By Creator',
            'user_id' => $user->id,
        ]);
    }

    public function test_the_owner_can_view_the_edit_form(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('projects.edit', $project))
            ->assertOk();
    }

    public function test_the_owner_can_update_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id, 'name' => 'Before']);

        $this->actingAs($user)
            ->patch(route('projects.update', $project), ['name' => 'After'])
            ->assertRedirect();

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'After']);
    }

    public function test_the_owner_can_delete_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'));

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_a_non_owner_cannot_view_the_edit_form(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('projects.edit', $project))
            ->assertForbidden();
    }

    public function test_a_non_owner_cannot_update_the_project(): void
    {
        $project = Project::factory()->create(['name' => 'Untouched']);

        $this->actingAs(User::factory()->create())
            ->patch(route('projects.update', $project), ['name' => 'Hacked'])
            ->assertForbidden();

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Untouched']);
    }

    public function test_a_non_owner_cannot_delete_the_project(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('projects.destroy', $project))
            ->assertForbidden();

        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_a_non_owner_does_not_see_edit_or_delete_buttons(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        // A non-owner sees the project but not the owner-only edit action.
        $this->actingAs(User::factory()->create())
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertDontSee(route('projects.edit', $project), false);

        // The owner does see it.
        $this->actingAs($owner)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee(route('projects.edit', $project), false);
    }

    public function test_the_owner_name_is_shown_on_the_project_list(): void
    {
        $owner = User::factory()->create(['name' => 'Distinctive Owner Name']);
        Project::factory()->create(['user_id' => $owner->id]);

        $this->actingAs(User::factory()->create())
            ->get(route('projects.index'))
            ->assertOk()
            ->assertSee('Distinctive Owner Name');
    }
}
