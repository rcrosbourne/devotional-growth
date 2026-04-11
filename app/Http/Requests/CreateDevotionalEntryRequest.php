<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\ScriptureReferenceFormat;
use Illuminate\Foundation\Http\FormRequest;

final class CreateDevotionalEntryRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'reflection_prompts' => ['nullable', 'string'],
            'adventist_insights' => ['nullable', 'string'],
            'scripture_references' => ['required', 'array', 'min:1'],
            'scripture_references.*' => ['required', 'string', new ScriptureReferenceFormat],
        ];
    }
}
