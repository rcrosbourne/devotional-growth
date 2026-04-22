<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\BibleStudy;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePassageRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'position' => ['required', 'integer', 'min:1'],
            'is_guided_path' => ['required', 'boolean'],
            'book' => ['required', 'string', 'max:64'],
            'chapter' => ['required', 'integer', 'min:1'],
            'verse_start' => ['required', 'integer', 'min:1'],
            'verse_end' => ['nullable', 'integer', 'min:1', 'gte:verse_start'],
            'passage_intro' => ['nullable', 'string'],
        ];
    }
}
