<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\BibleStudy;

use App\Models\WordStudy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreWordHighlightRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'word_study_id' => ['required', 'integer', Rule::exists(WordStudy::class, 'id')],
            'verse_number' => ['required', 'integer', 'min:1'],
            'word_index_in_verse' => ['required', 'integer', 'min:0'],
            'display_word' => ['required', 'string', 'max:64'],
        ];
    }
}
