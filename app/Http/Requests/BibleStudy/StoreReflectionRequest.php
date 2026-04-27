<?php

declare(strict_types=1);

namespace App\Http\Requests\BibleStudy;

use App\Models\BibleStudyTheme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreReflectionRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'theme_id' => ['nullable', 'integer', Rule::exists(BibleStudyTheme::class, 'id')],
            'book' => ['required', 'string', 'max:64'],
            'chapter' => ['required', 'integer', 'min:1'],
            'verse_start' => ['required', 'integer', 'min:1'],
            'verse_end' => ['nullable', 'integer', 'min:1', 'gte:verse_start'],
            'verse_number' => ['nullable', 'integer', 'min:1'],
            'body' => ['required', 'string', 'min:1'],
            'is_shared_with_partner' => ['required', 'boolean'],
        ];
    }
}
