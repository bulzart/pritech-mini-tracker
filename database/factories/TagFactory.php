<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tag>
 */
final class TagFactory extends Factory
{
    protected $model = Tag::class;

    /**
     * Realistic tag vocabulary. Drawn uniquely so the unique `name` column is
     * never violated within a single run.
     *
     * @var list<string>
     */
    private const array TAG_NAMES = [
        'backend', 'frontend', 'bug', 'feature', 'urgent', 'design', 'QA',
        'documentation', 'performance', 'security', 'refactor', 'ux',
        'infrastructure', 'testing', 'release',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement(self::TAG_NAMES),
            'color' => $this->faker->boolean(80) ? $this->faker->hexColor() : null,
        ];
    }
}
