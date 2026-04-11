<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class LinkPartnerRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                Rule::exists(User::class, 'email'),
                Rule::notIn([$this->user()?->email]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.not_in' => 'You cannot link yourself as a partner.',
        ];
    }
}
