<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Comment;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Tag;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RelationshipsAndCascadesTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_has_many_issues(): void
    {
        $project = Project::factory()->create();
        Issue::factory()->count(3)->for($project)->create();

        $this->assertCount(3, $project->issues);
        $this->assertInstanceOf(Issue::class, $project->issues->first());
    }

    public function test_issue_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $issue = Issue::factory()->for($project)->create();

        $this->assertInstanceOf(Project::class, $issue->project);
        $this->assertSame($project->id, $issue->project->id);
    }

    public function test_issue_has_many_comments(): void
    {
        $issue = Issue::factory()->create();
        Comment::factory()->count(2)->for($issue)->create();

        $this->assertCount(2, $issue->comments);
        $this->assertInstanceOf(Comment::class, $issue->comments->first());
    }

    public function test_comment_belongs_to_issue(): void
    {
        $issue = Issue::factory()->create();
        $comment = Comment::factory()->for($issue)->create();

        $this->assertInstanceOf(Issue::class, $comment->issue);
        $this->assertSame($issue->id, $comment->issue->id);
    }

    public function test_issue_belongs_to_many_tags(): void
    {
        $issue = Issue::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $issue->tags()->attach($tags);

        $this->assertCount(2, $issue->refresh()->tags);
        $this->assertInstanceOf(Tag::class, $issue->tags->first());
    }

    public function test_tag_belongs_to_many_issues(): void
    {
        $tag = Tag::factory()->create();
        $issues = Issue::factory()->count(2)->create();

        $tag->issues()->attach($issues);

        $this->assertCount(2, $tag->refresh()->issues);
        $this->assertInstanceOf(Issue::class, $tag->issues->first());
    }

    public function test_tag_name_uniqueness_is_enforced(): void
    {
        Tag::factory()->create(['name' => 'backend']);

        $this->expectException(QueryException::class);

        Tag::factory()->create(['name' => 'backend']);
    }

    public function test_issue_tag_pivot_rejects_duplicate_pairs(): void
    {
        $issue = Issue::factory()->create();
        $tag = Tag::factory()->create();

        $issue->tags()->attach($tag->id);

        $this->expectException(QueryException::class);

        // Attaching the same pair again must violate the unique compound index.
        $issue->tags()->attach($tag->id);
    }

    public function test_deleting_a_project_deletes_its_issues(): void
    {
        $project = Project::factory()->create();
        Issue::factory()->count(3)->for($project)->create();

        $project->delete();

        $this->assertDatabaseCount('issues', 0);
    }

    public function test_deleting_an_issue_deletes_its_comments(): void
    {
        $issue = Issue::factory()->create();
        Comment::factory()->count(3)->for($issue)->create();

        $issue->delete();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_deleting_an_issue_deletes_its_pivot_rows(): void
    {
        $issue = Issue::factory()->create();
        $issue->tags()->attach(Tag::factory()->count(2)->create());

        $this->assertDatabaseCount('issue_tag', 2);

        $issue->delete();

        $this->assertDatabaseCount('issue_tag', 0);
    }

    public function test_deleting_a_tag_deletes_its_pivot_rows(): void
    {
        $tag = Tag::factory()->create();
        $tag->issues()->attach(Issue::factory()->count(2)->create());

        $this->assertDatabaseCount('issue_tag', 2);

        $tag->delete();

        $this->assertDatabaseCount('issue_tag', 0);
    }
}
