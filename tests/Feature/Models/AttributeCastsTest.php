<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Issue;
use App\Models\Project;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AttributeCastsTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_start_date_is_cast_to_a_date(): void
    {
        $project = Project::factory()->create(['start_date' => '2026-01-15'])->fresh();

        $this->assertInstanceOf(CarbonInterface::class, $project->start_date);
        $this->assertSame('2026-01-15', $project->start_date->format('Y-m-d'));
    }

    public function test_project_deadline_is_cast_to_a_date(): void
    {
        $project = Project::factory()->create(['deadline' => '2026-02-20'])->fresh();

        $this->assertInstanceOf(CarbonInterface::class, $project->deadline);
        $this->assertSame('2026-02-20', $project->deadline->format('Y-m-d'));
    }

    public function test_issue_due_date_is_cast_to_a_date(): void
    {
        $issue = Issue::factory()->create(['due_date' => '2026-03-10'])->fresh();

        $this->assertInstanceOf(CarbonInterface::class, $issue->due_date);
        $this->assertSame('2026-03-10', $issue->due_date->format('Y-m-d'));
    }
}
