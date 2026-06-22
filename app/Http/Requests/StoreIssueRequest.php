<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Issue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        // No authorization layer exists in this checkpoint — access control is
        // a documented follow-up (see CHECKPOINT.md). Validation is the single
        // responsibility of this class.
        return true;
    }

    /**
     * Allowed status/priority sets come from the Issue model so validation,
     * factories, and the UI all share one source of truth.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(Issue::STATUSES)],
            'priority' => ['required', 'string', Rule::in(Issue::PRIORITIES)],
            'due_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'project_id' => 'project',
            'due_date' => 'due date',
        ];
    }
}
