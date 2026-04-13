<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\DevotionalEntry;
use App\Models\Lesson;
use App\Models\LessonDay;
use App\Models\ScriptureReference;
use App\Models\WordStudy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateBookmarkRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'bookmarkable_type' => ['required', 'string', Rule::in([
                DevotionalEntry::class,
                ScriptureReference::class,
                WordStudy::class,
                Lesson::class,
                LessonDay::class,
            ])],
            'bookmarkable_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
