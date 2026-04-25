<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\BibleStudy;

use Illuminate\Foundation\Http\FormRequest;

final class StoreDraftRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:2', 'max:120'],
        ];
    }
}
