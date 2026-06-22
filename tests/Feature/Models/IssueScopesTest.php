<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Issue;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class IssueScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_scope_filters_and_ignores_empty_values(): void
    {
        Issue::factory()->open()->count(2)->create();
        Issue::factory()->closed()->count(3)->create();

        $this->assertCount(2, Issue::status('open')->get());
        $this->assertCount(3, Issue::status('closed')->get());

        // Null/empty must be a no-op (returns all 5 issues).
        $this->assertCount(5, Issue::status(null)->get());
        $this->assertCount(5, Issue::status('')->get());
    }

    public function test_priority_scope_filters_and_ignores_empty_values(): void
    {
        Issue::factory()->count(2)->create(['priority' => 'high']);
        Issue::factory()->count(4)->create(['priority' => 'low']);

        $this->assertCount(2, Issue::priority('high')->get());
        $this->assertCount(4, Issue::priority('low')->get());

        $this->assertCount(6, Issue::priority(null)->get());
        $this->assertCount(6, Issue::priority('')->get());
    }

    public function test_tag_scope_filters_by_tag_name_and_ignores_empty_values(): void
    {
        $backend = Tag::factory()->create(['name' => 'backend']);

        $tagged = Issue::factory()->create();
        $tagged->tags()->attach($backend);

        Issue::factory()->count(2)->create(); // untagged

        $result = Issue::tag('backend')->get();

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is($tagged));

        // Null/empty must be a no-op (returns all 3 issues).
        $this->assertCount(3, Issue::tag(null)->get());
        $this->assertCount(3, Issue::tag('')->get());
    }
}
