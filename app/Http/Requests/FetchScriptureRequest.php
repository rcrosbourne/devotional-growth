<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class FetchScriptureRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'book' => ['required', 'string'],
            'chapter' => ['required', 'integer', 'min:1'],
            'verse_start' => ['required', 'integer', 'min:1'],
            'verse_end' => ['nullable', 'integer', 'min:1'],
            'bible_version' => ['nullable', 'string', 'in:KJV,NIV,ESV,NKJV,NLT,ASV,WEB'],
        ];
    }
}
