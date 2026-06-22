<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        // No authorization layer exists in this checkpoint — access control is
        // a documented follow-up (see CHECKPOINT.md). Allowing the request here
        // keeps validation as the single responsibility of this class.
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            // The chronological check only applies when a start_date is present,
            // so a deadline supplied on its own is still accepted.
            'deadline' => [
                'nullable',
                'date',
                Rule::when($this->filled('start_date'), ['after_or_equal:start_date']),
            ],
        ];
    }

    /**
     * Friendlier names for validation messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'start_date' => 'start date',
            'deadline' => 'deadline',
        ];
    }
}
