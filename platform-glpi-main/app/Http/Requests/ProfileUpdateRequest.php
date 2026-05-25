<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'              => ['nullable', 'string', 'max:255'],
            'first_name'        => ['nullable', 'string', 'max:100'],
            'last_name'         => ['nullable', 'string', 'max:100'],
            'birthday'          => ['nullable', 'date', 'before:today'],
            'gender'            => ['nullable', 'in:male,female,other'],
            'email'             => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone'             => ['nullable', 'string', 'max:20'],
            'phone_mobile'      => ['nullable', 'string', 'max:20'],
            'whatsapp'          => ['nullable', 'string', 'max:20'],
            'teams_email'       => ['nullable', 'email', 'max:255'],
            'teams_webhook_url' => ['nullable', 'url', 'max:2048'],
            'timezone'          => ['nullable', 'string', 'max:50'],
            'locale'            => ['nullable', 'string', 'max:10'],
            'avatar'            => ['nullable', 'image', 'max:2048'],
        ];
    }
}