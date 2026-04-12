<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Theme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveAiContentRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'ai_generation_log_id' => ['required', 'integer', 'exists:ai_generation_logs,id'],
            'theme_id' => ['nullable', 'integer', 'exists:themes,id'],
            'new_theme_name' => ['required_without:theme_id', 'nullable', 'string', 'max:255', Rule::unique(Theme::class, 'name')],
            'new_theme_description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
