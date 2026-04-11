<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateNotificationPreferencesRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'completion_notifications' => ['required', 'boolean'],
            'observation_notifications' => ['required', 'boolean'],
            'new_theme_notifications' => ['required', 'boolean'],
            'reminder_notifications' => ['required', 'boolean'],
        ];
    }
}
