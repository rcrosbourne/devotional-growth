<?php

declare(strict_types=1);

namespace App\Http\Requests\BibleStudy;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateReflectionRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1'],
            'is_shared_with_partner' => ['required', 'boolean'],
        ];
    }
}
