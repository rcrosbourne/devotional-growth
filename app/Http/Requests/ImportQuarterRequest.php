<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ImportQuarterRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'quarter_code' => ['nullable', 'string', 'max:4', 'regex:/^\d{2}[a-d]$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quarter_code.regex' => 'Quarter code must be in format like "26b" (2-digit year + a/b/c/d for quarter).',
        ];
    }
}
