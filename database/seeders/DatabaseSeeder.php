<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Seeds a coherent, demo-ready data set so the application is understandable
 * the moment a reviewer opens it: real-looking projects, a mix of issue
 * statuses and priorities, a shared tag vocabulary, and threaded comments.
 *
 * Safe to re-run with `php artisan migrate:fresh --seed`. Tags are created
 * with firstOrCreate so re-seeding without a fresh migrate does not violate
 * the unique tag name.
 */
final class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Convenience account kept for future authenticated features.
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $tags = $this->seedTags();

        // The very first issue gets a deliberately large comment thread so the
        // paginated comments endpoint (5 per page) is demonstrable immediately.
        $firstIssueSeeded = false;

        foreach ($this->projectBlueprints() as $blueprint) {
            $project = Project::query()->create($blueprint);

            Issue::factory()
                ->count(fake()->numberBetween(7, 10))
                ->for($project)
                ->create()
                ->each(function (Issue $issue) use ($tags, &$firstIssueSeeded): void {
                    // Each issue carries a small, random subset of the shared tags.
                    $issue->tags()->attach(
                        $tags->random(fake()->numberBetween(1, 4))->pluck('id')->all(),
                    );

                    $commentCount = $firstIssueSeeded
                        ? fake()->numberBetween(3, 6)
                        : 12;
                    $firstIssueSeeded = true;

                    Comment::factory()
                        ->count($commentCount)
                        ->for($issue)
                        ->create();
                });
        }
    }

    /**
     * Create the shared tag vocabulary with stable colours.
     *
     * @return Collection<int, Tag>
     */
    private function seedTags(): Collection
    {
        $definitions = [
            ['name' => 'backend', 'color' => '#2563eb'],
            ['name' => 'frontend', 'color' => '#7c3aed'],
            ['name' => 'bug', 'color' => '#dc2626'],
            ['name' => 'feature', 'color' => '#16a34a'],
            ['name' => 'urgent', 'color' => '#ea580c'],
            ['name' => 'design', 'color' => '#db2777'],
            ['name' => 'QA', 'color' => '#0d9488'],
            ['name' => 'documentation', 'color' => '#ca8a04'],
        ];

        return collect($definitions)->map(
            fn (array $definition): Tag => Tag::query()->firstOrCreate(
                ['name' => $definition['name']],
                ['color' => $definition['color']],
            ),
        );
    }

    /**
     * Six named projects. A couple intentionally leave start_date/deadline
     * null to exercise the nullable scheduling fields.
     *
     * @return list<array<string, string|null>>
     */
    private function projectBlueprints(): array
    {
        return [
            [
                'name' => 'Website Redesign',
                'description' => 'Rebuild the public marketing site with a faster, accessible front end.',
                'start_date' => '2026-05-01',
                'deadline' => '2026-08-15',
            ],
            [
                'name' => 'Mobile App v2',
                'description' => 'Second major release of the customer mobile app, including offline support.',
                'start_date' => '2026-04-15',
                'deadline' => '2026-09-30',
            ],
            [
                'name' => 'Billing System Overhaul',
                'description' => 'Replace the legacy invoicing flow and reconcile against the new ledger.',
                'start_date' => '2026-06-01',
                'deadline' => '2026-07-31',
            ],
            [
                'name' => 'Internal Analytics Dashboard',
                'description' => 'Self-serve reporting for the operations team with scheduled exports.',
                'start_date' => '2026-06-10',
                'deadline' => null,
            ],
            [
                'name' => 'Customer Support Desk',
                'description' => 'Ticketing and macros for the support team, integrated with email.',
                'start_date' => null,
                'deadline' => null,
            ],
            [
                'name' => 'API Platform',
                'description' => 'Public REST API with versioning, rate limiting, and developer docs.',
                'start_date' => '2026-03-20',
                'deadline' => '2026-10-15',
            ],
        ];
    }
}
