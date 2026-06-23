<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
final class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * Names that read like real small-team initiatives rather than lorem text.
     *
     * @var list<string>
     */
    private const array PROJECT_NAMES = [
        'Website Redesign',
        'Mobile App v2',
        'Customer Portal',
        'Billing System Overhaul',
        'Internal Analytics Dashboard',
        'API Platform',
        'Onboarding Flow Revamp',
        'Marketing Site Refresh',
        'Data Warehouse Migration',
        'Support Desk Tooling',
        'Design System',
        'Checkout Optimisation',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // ~70% of projects have a start date; of those, ~80% have a deadline.
        $startDate = $this->faker->boolean(70)
            ? $this->faker->dateTimeBetween('-1 month', '+1 month')
            : null;

        // Keep the data valid against the deadline >= start_date rule by always
        // placing the deadline after the start date.
        $deadline = $startDate !== null && $this->faker->boolean(80)
            ? (clone $startDate)->modify('+'.$this->faker->numberBetween(14, 90).' days')
            : null;

        return [
            // Each project gets its own owner by default; tests that need a
            // specific owner pass ->for($user) or ['user_id' => $user->id].
            'user_id' => User::factory(),
            'name' => $this->faker->randomElement(self::PROJECT_NAMES),
            'description' => $this->faker->boolean(85) ? $this->faker->paragraph() : null,
            'start_date' => $startDate,
            'deadline' => $deadline,
        ];
    }
}
