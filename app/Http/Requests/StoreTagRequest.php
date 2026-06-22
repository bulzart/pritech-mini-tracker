<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        // No authorization layer exists in this checkpoint — access control is
        // a documented follow-up (see CHECKPOINT.md).
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // Unique name is enforced both here (friendly message) and by the
            // unique index on tags.name (race-safe at the database level).
            'name' => ['required', 'string', 'max:255', 'unique:tags,name'],
            'color' => ['nullable', 'string', 'max:50'],
        ];
    }
}
