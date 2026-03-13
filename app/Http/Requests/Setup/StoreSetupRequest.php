<?php

namespace App\Http\Requests\Setup;

use Illuminate\Foundation\Http\FormRequest;

class StoreSetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    public function rules(): array
    {
        $tzPhone = '/^(?:\+?255|0)(?:6|7)\d{8}$/';

        return [
            'diocese.name' => ['bail', 'required', 'string', 'max:255'],
            'diocese.archbishop_name' => ['bail', 'required', 'string', 'max:255'],
            'diocese.established_year' => ['bail', 'required', 'integer', 'min:1800', 'max:2100'],
            'diocese.address' => ['bail', 'required', 'string', 'max:255'],
            'diocese.phone' => ['bail', 'required', 'string', 'max:20', 'regex:'.$tzPhone],
            'diocese.email' => ['bail', 'required', 'email', 'max:255'],
            'diocese.website' => ['bail', 'nullable', 'url', 'max:255'],
            'diocese.country' => ['bail', 'required', 'string', 'max:255'],

            'parish.name' => ['bail', 'required', 'string', 'max:255'],
            'parish.code' => ['bail', 'nullable', 'string', 'max:50'],
            'parish.patron_saint' => ['bail', 'required', 'string', 'max:255'],
            'parish.established_year' => ['bail', 'required', 'integer', 'min:1800', 'max:2100'],
            'parish.address' => ['bail', 'required', 'string', 'max:255'],
            'parish.phone' => ['bail', 'required', 'string', 'max:20', 'regex:'.$tzPhone],
            'parish.email' => ['bail', 'required', 'email', 'max:255'],
        ];
    }
}
