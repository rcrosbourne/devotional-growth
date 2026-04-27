<?php

declare(strict_types=1);

namespace App\Http\Requests\BibleStudy;

use Illuminate\Foundation\Http\FormRequest;

final class PassageQueryRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'theme' => ['nullable', 'string', 'max:128'],
            'book' => ['required', 'string', 'max:64'],
            'chapter' => ['required', 'integer', 'min:1'],
            'verse_start' => ['required', 'integer', 'min:1'],
            'verse_end' => ['nullable', 'integer', 'min:1', 'gte:verse_start'],
            'translation' => ['nullable', 'string', 'in:KJV,NKJV,NIV,NLT,ASV,WEB,BBE,DARBY,HEBREW'],
        ];
    }
}
