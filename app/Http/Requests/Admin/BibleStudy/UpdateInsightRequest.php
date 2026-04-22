<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\BibleStudy;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateInsightRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'interpretation' => ['required', 'string'],
            'application' => ['required', 'string'],
            'cross_references' => ['array'],
            'cross_references.*.book' => ['required', 'string'],
            'cross_references.*.chapter' => ['required', 'integer', 'min:1'],
            'cross_references.*.verse_start' => ['required', 'integer', 'min:1'],
            'cross_references.*.verse_end' => ['nullable', 'integer', 'min:1'],
            'cross_references.*.note' => ['nullable', 'string'],
            'literary_context' => ['required', 'string'],
        ];
    }
}
