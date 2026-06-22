<?php

declare(strict_types=1);

namespace Tests\Feature\Issues;

use App\Models\Comment;
use App\Models\Issue;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class IssueCommentApiTest extends TestCase
{
    use RefreshDatabase;

    private function issue(): Issue
    {
        return Issue::factory()->for(Project::factory())->create();
    }

    /** #40 */
    public function test_comments_index_returns_json(): void
    {
        $issue = $this->issue();
        Comment::factory()->count(3)->for($issue)->create();

        $this->getJson(route('issues.comments.index', $issue))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'author_name', 'body', 'created_at']],
                'meta',
                'links',
            ]);
    }

    /** #41 */
    public function test_comments_endpoint_is_paginated(): void
    {
        $issue = $this->issue();
        Comment::factory()->count(3)->for($issue)->create();

        $this->getJson(route('issues.comments.index', $issue))
            ->assertOk()
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonStructure(['meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    /** #42 */
    public function test_comments_are_newest_first(): void
    {
        $issue = $this->issue();
        $oldest = Comment::factory()->for($issue)->create(['created_at' => now()->subDays(2)]);
        $middle = Comment::factory()->for($issue)->create(['created_at' => now()->subDay()]);
        $newest = Comment::factory()->for($issue)->create(['created_at' => now()]);

        $this->getJson(route('issues.comments.index', $issue))
            ->assertOk()
            ->assertJsonPath('data.0.id', $newest->id)
            ->assertJsonPath('data.1.id', $middle->id)
            ->assertJsonPath('data.2.id', $oldest->id);
    }

    /** #43, #44, #45 */
    public function test_posting_a_comment_creates_it_and_returns_201_with_json(): void
    {
        $issue = $this->issue();

        $response = $this->postJson(route('issues.comments.store', $issue), [
            'author_name' => 'Ada Lovelace',
            'body' => 'This is a test comment.',
        ]);

        $response->assertCreated(); // #44 — HTTP 201
        $response->assertJsonPath('data.author_name', 'Ada Lovelace'); // #45
        $response->assertJsonPath('data.body', 'This is a test comment.');

        // #43 — persisted
        $this->assertDatabaseHas('comments', [
            'issue_id' => $issue->id,
            'author_name' => 'Ada Lovelace',
            'body' => 'This is a test comment.',
        ]);
    }

    /** #46 */
    public function test_empty_author_name_returns_422(): void
    {
        $issue = $this->issue();

        $this->postJson(route('issues.comments.store', $issue), [
            'author_name' => '',
            'body' => 'Body present.',
        ])->assertStatus(422);
    }

    /** #47 */
    public function test_empty_body_returns_422(): void
    {
        $issue = $this->issue();

        $this->postJson(route('issues.comments.store', $issue), [
            'author_name' => 'Someone',
            'body' => '',
        ])->assertStatus(422);
    }

    /** #48 */
    public function test_validation_errors_return_json(): void
    {
        $issue = $this->issue();

        $this->postJson(route('issues.comments.store', $issue), [
            'author_name' => '',
            'body' => '',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['author_name', 'body']);
    }

    /** #49 */
    public function test_comment_belongs_to_the_correct_issue(): void
    {
        $target = $this->issue();
        $other = $this->issue();

        $response = $this->postJson(route('issues.comments.store', $target), [
            'author_name' => 'Owner',
            'body' => 'Belongs to target issue.',
        ]);

        $commentId = $response->json('data.id');

        $this->assertDatabaseHas('comments', [
            'id' => $commentId,
            'issue_id' => $target->id,
        ]);
        $this->assertDatabaseMissing('comments', [
            'id' => $commentId,
            'issue_id' => $other->id,
        ]);
    }

    /** #50 */
    public function test_comments_page_two_works_when_enough_comments_exist(): void
    {
        $issue = $this->issue();
        // 8 comments at 5 per page → page 2 holds the remaining 3.
        Comment::factory()->count(8)->for($issue)->create();

        $this->getJson(route('issues.comments.index', ['issue' => $issue, 'page' => 2]))
            ->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonCount(3, 'data');
    }
}
