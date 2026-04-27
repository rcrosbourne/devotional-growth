<?php

declare(strict_types=1);

namespace App\Http\Requests\BibleStudy;

use Illuminate\Foundation\Http\FormRequest;

final class SearchQueryRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:1', 'max:128'],
        ];
    }
}
