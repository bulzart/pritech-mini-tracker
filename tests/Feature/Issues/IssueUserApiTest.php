<?php

declare(strict_types=1);

namespace Tests\Feature\Issues;

use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class IssueUserApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * An issue whose project is owned by $owner — so $owner may manage its
     * assignments and anyone else is a non-owner.
     */
    private function issueOwnedBy(User $owner): Issue
    {
        $project = Project::factory()->create(['user_id' => $owner->id]);

        return Issue::factory()->for($project)->create();
    }

    public function test_issue_user_pivot_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('issue_user'));
    }

    public function test_an_issue_can_have_many_assigned_users(): void
    {
        $issue = Issue::factory()->create();
        $users = User::factory()->count(2)->create();

        $issue->assignees()->attach($users);

        $this->assertCount(2, $issue->refresh()->assignees);
        $this->assertInstanceOf(User::class, $issue->assignees->first());
    }

    public function test_a_user_can_have_many_assigned_issues(): void
    {
        $user = User::factory()->create();
        $issues = Issue::factory()->count(2)->create();

        $user->assignedIssues()->attach($issues);

        $this->assertCount(2, $user->refresh()->assignedIssues);
        $this->assertInstanceOf(Issue::class, $user->assignedIssues->first());
    }

    public function test_assigned_users_appear_on_the_issue_detail_page(): void
    {
        $owner = User::factory()->create();
        $issue = $this->issueOwnedBy($owner);
        $member = User::factory()->create([
            'name' => 'Assigned Member Name',
            'email' => 'assigned.member@example.com',
        ]);
        $issue->assignees()->attach($member);

        $this->actingAs($owner)
            ->get(route('issues.show', $issue))
            ->assertOk()
            ->assertSee('Assigned Member Name')
            ->assertSee('assigned.member@example.com');
    }

    public function test_the_assign_endpoint_assigns_a_user(): void
    {
        $owner = User::factory()->create();
        $issue = $this->issueOwnedBy($owner);
        $member = User::factory()->create();

        $this->actingAs($owner)
            ->postJson(route('issues.users.attach', [$issue, $member]))
            ->assertOk();

        $this->assertDatabaseHas('issue_user', [
            'issue_id' => $issue->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_the_assign_endpoint_returns_json(): void
    {
        $owner = User::factory()->create();
        $issue = $this->issueOwnedBy($owner);
        $member = User::factory()->create(['name' => 'JSON User']);

        $this->actingAs($owner)
            ->postJson(route('issues.users.attach', [$issue, $member]))
            ->assertOk()
            ->assertJson([
                'assigned' => true,
                'user' => ['id' => $member->id, 'name' => 'JSON User'],
            ])
            ->assertJsonStructure(['assigned', 'message', 'user' => ['id', 'name', 'email']]);
    }

    public function test_duplicate_assignment_does_not_create_a_duplicate_row(): void
    {
        $owner = User::factory()->create();
        $issue = $this->issueOwnedBy($owner);
        $member = User::factory()->create();

        $this->actingAs($owner)->postJson(route('issues.users.attach', [$issue, $member]))->assertOk();
        $this->actingAs($owner)->postJson(route('issues.users.attach', [$issue, $member]))->assertOk();

        $this->assertDatabaseCount('issue_user', 1);
    }

    public function test_the_unassign_endpoint_removes_the_assignment(): void
    {
        $owner = User::factory()->create();
        $issue = $this->issueOwnedBy($owner);
        $member = User::factory()->create();
        $issue->assignees()->attach($member);

        $this->actingAs($owner)
            ->deleteJson(route('issues.users.detach', [$issue, $member]))
            ->assertOk()
            ->assertJson(['assigned' => false]);

        $this->assertDatabaseMissing('issue_user', [
            'issue_id' => $issue->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_unassigning_a_missing_assignment_does_not_crash(): void
    {
        $owner = User::factory()->create();
        $issue = $this->issueOwnedBy($owner);
        $member = User::factory()->create();

        $this->actingAs($owner)
            ->deleteJson(route('issues.users.detach', [$issue, $member]))
            ->assertOk()
            ->assertJson(['assigned' => false]);

        $this->assertDatabaseCount('issue_user', 0);
    }

    public function test_a_guest_cannot_assign_users(): void
    {
        $issue = Issue::factory()->create();
        $member = User::factory()->create();

        $this->postJson(route('issues.users.attach', [$issue, $member]))
            ->assertUnauthorized();

        $this->assertDatabaseCount('issue_user', 0);
    }

    public function test_a_non_owner_cannot_assign_users(): void
    {
        $owner = User::factory()->create();
        $issue = $this->issueOwnedBy($owner);
        $member = User::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->postJson(route('issues.users.attach', [$issue, $member]))
            ->assertForbidden();

        $this->assertDatabaseCount('issue_user', 0);
    }

    public function test_seeded_issues_can_have_assignees(): void
    {
        $this->seed();

        $this->assertTrue(Issue::has('assignees')->exists());
        $this->assertGreaterThan(0, DB::table('issue_user')->count());
    }
}
