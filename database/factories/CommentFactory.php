<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Issue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
final class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * Lines that read like real issue discussion.
     *
     * @var list<string>
     */
    private const array COMMENT_BODIES = [
        'I can reproduce this on staging — looking into it now.',
        'Assigning this to myself, should have a fix by end of day.',
        'This is blocked by the pending API change, holding off for now.',
        'Fixed in the latest commit, can someone verify on their machine?',
        'Can we bump the priority on this? It is affecting a few customers.',
        'Added tests and a small refactor, ready for review.',
        'Confirmed resolved on production after the deploy.',
        'I think the root cause is the cache not being invalidated.',
        'Let us split this into a separate follow-up ticket.',
        'Good catch — I missed the edge case where the list is empty.',
        'Rebased on main and re-ran the suite, all green.',
        'Do we have a reproduction with the exact steps?',
        'Closing as a duplicate of the earlier report.',
        'Pushed a hotfix, will monitor the error rate overnight.',
        'Nice work, this is much faster than the previous version.',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'issue_id' => Issue::factory(),
            'author_name' => $this->faker->name(),
            'body' => $this->faker->randomElement(self::COMMENT_BODIES),
        ];
    }
}
