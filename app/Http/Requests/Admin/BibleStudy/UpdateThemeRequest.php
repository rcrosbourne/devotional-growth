<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\BibleStudy;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateThemeRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:2', 'max:120'],
            'short_description' => ['required', 'string', 'max:255'],
            'long_intro' => ['required', 'string'],
        ];
    }
}
