<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\BibleStudy;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateHistoricalContextRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'setting' => ['required', 'string'],
            'author' => ['required', 'string'],
            'date_range' => ['required', 'string'],
            'audience' => ['required', 'string'],
            'historical_events' => ['required', 'string'],
        ];
    }
}
