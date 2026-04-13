<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class FetchChapterRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'book' => ['required', 'string'],
            'chapter' => ['required', 'integer', 'min:1'],
            'bible_version' => ['nullable', 'string', 'in:KJV,NKJV,NIV,NLT,ASV,WEB,BBE,DARBY,HEBREW'],
        ];
    }
}
