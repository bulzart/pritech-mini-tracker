<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Issue;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Issue>
 */
final class IssueFactory extends Factory
{
    protected $model = Issue::class;

    /**
     * Titles that look like real tasks, bugs, and features.
     *
     * @var list<string>
     */
    private const array ISSUE_TITLES = [
        'Fix login redirect loop',
        'Add dark mode toggle',
        'Improve dashboard load time',
        'Update payment webhook handler',
        'Crash when saving an empty profile',
        'Implement CSV export for reports',
        'Refactor notification service',
        'Broken pagination on search results',
        'Add unit tests for the billing module',
        'Mobile layout overflows on small screens',
        'Validate email address on signup',
        'Cache expensive project queries',
        'Migrate legacy avatars to object storage',
        'Add keyboard shortcuts to the editor',
        'Investigate slow API response on /orders',
        'Support password reset via email',
        'Throttle failed login attempts',
        'Document the deployment process',
        'Upgrade to the latest framework release',
        'Fix timezone handling in due dates',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => $this->faker->randomElement(self::ISSUE_TITLES),
            'description' => $this->faker->paragraphs($this->faker->numberBetween(1, 3), true),
            'status' => $this->faker->randomElement(Issue::STATUSES),
            'priority' => $this->faker->randomElement(Issue::PRIORITIES),
            // ~60% of issues are scheduled; the rest are unscheduled (null).
            'due_date' => $this->faker->boolean(60)
                ? $this->faker->dateTimeBetween('-1 week', '+2 months')
                : null,
        ];
    }

    public function open(): self
    {
        return $this->state(['status' => 'open']);
    }

    public function inProgress(): self
    {
        return $this->state(['status' => 'in_progress']);
    }

    public function closed(): self
    {
        return $this->state(['status' => 'closed']);
    }
}
