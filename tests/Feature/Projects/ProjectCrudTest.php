<?php

declare(strict_types=1);

namespace Tests\Feature\Projects;

use App\Models\Issue;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProjectCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_projects_index(): void
    {
        $this->get('/')->assertRedirect(route('projects.index'));
    }

    public function test_projects_index_returns_ok(): void
    {
        $this->get(route('projects.index'))->assertOk();
    }

    public function test_projects_index_displays_existing_projects(): void
    {
        $projects = Project::factory()->count(3)->create();

        $response = $this->get(route('projects.index'));

        $response->assertOk();

        foreach ($projects as $project) {
            $response->assertSee($project->name);
        }
    }

    public function test_create_page_returns_ok(): void
    {
        $this->get(route('projects.create'))->assertOk();
    }

    public function test_a_valid_project_can_be_created(): void
    {
        $response = $this->post(route('projects.store'), [
            'name' => 'Alpha Project',
            'description' => 'A project created during testing.',
            'start_date' => '2026-01-01',
            'deadline' => '2026-02-01',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'name' => 'Alpha Project',
            'description' => 'A project created during testing.',
        ]);
    }

    public function test_an_invalid_project_name_shows_a_validation_error(): void
    {
        $response = $this->from(route('projects.create'))
            ->post(route('projects.store'), ['name' => '']);

        $response->assertRedirect(route('projects.create'));
        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('projects', 0);
    }

    public function test_a_deadline_before_the_start_date_fails_validation(): void
    {
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
        $project = Project::factory()->create();

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee($project->name);
    }

    public function test_project_show_displays_related_issues(): void
    {
        $project = Project::factory()->create();
        Issue::factory()->for($project)->create(['title' => 'Visible Issue Title']);

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Visible Issue Title');
    }

    public function test_edit_page_returns_ok(): void
    {
        $project = Project::factory()->create();

        $this->get(route('projects.edit', $project))
            ->assertOk()
            ->assertSee($project->name);
    }

    public function test_a_project_can_be_updated(): void
    {
        $project = Project::factory()->create(['name' => 'Old Name']);

        $response = $this->patch(route('projects.update', $project), [
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
        $project = Project::factory()->create();

        $this->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'));
    }

    public function test_a_deleted_project_no_longer_exists(): void
    {
        $project = Project::factory()->create();

        $this->delete(route('projects.destroy', $project));

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }
}
