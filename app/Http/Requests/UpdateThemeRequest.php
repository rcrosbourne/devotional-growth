<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Theme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateThemeRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(Theme::class)->ignore($this->route('theme'))],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
