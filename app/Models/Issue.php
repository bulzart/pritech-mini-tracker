<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\IssueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['project_id', 'title', 'description', 'status', 'priority', 'due_date'])]
final class Issue extends Model
{
    /** @use HasFactory<IssueFactory> */
    use HasFactory;

    /**
     * Allowed workflow states. The application — not a DB enum — owns this set
     * so it can be reused for validation, factories, and UI.
     *
     * @var list<string>
     */
    public const array STATUSES = ['open', 'in_progress', 'closed'];

    /**
     * @var list<string>
     */
    public const array PRIORITIES = ['low', 'medium', 'high'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'issue_tag');
    }

    /**
     * Filter by status. A null/empty value is a no-op so the scope can be
     * driven directly by optional request input.
     */
    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $query->when(
            filled($status),
            fn (Builder $query): Builder => $query->where('status', $status),
        );
    }

    public function scopePriority(Builder $query, ?string $priority): Builder
    {
        return $query->when(
            filled($priority),
            fn (Builder $query): Builder => $query->where('priority', $priority),
        );
    }

    /**
     * Filter to issues carrying a tag (matched by tag name). Null/empty is a
     * no-op.
     */
    public function scopeTag(Builder $query, ?string $tag): Builder
    {
        return $query->when(
            filled($tag),
            fn (Builder $query): Builder => $query->whereHas(
                'tags',
                fn (Builder $tags): Builder => $tags->where('name', $tag),
            ),
        );
    }
}
