<?php

namespace App\Http\Requests\Institution;

use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Clergy\Institution;

class StoreInstitutionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        if (is_string($name)) {
            $key = mb_strtolower(trim(preg_replace('/\s+/', ' ', strip_tags($name)) ?? ''), 'UTF-8');
            $this->merge(['name_key' => $key]);
        }

        $contact = $this->input('contact');
        if (is_string($contact)) {
            $this->merge([
                'contact' => PhoneNormalizer::normalize($contact),
            ]);
        }

        foreach (['name', 'type', 'location', 'country'] as $key) {
            $v = $this->input($key);
            if (is_string($v)) {
                $this->merge([$key => trim(strip_tags($v))]);
            }
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('institutions.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'bail',
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $key = mb_strtolower(trim(preg_replace('/\s+/', ' ', strip_tags((string) $value)) ?? ''), 'UTF-8');
                    if ($key === '') {
                        return;
                    }

                    $exists =Institution::query()
                        ->where('name_key', $key)
                        ->exists();

                    if ($exists) {
                        $fail('Institution name already exists.');
                    }
                },
            ],
            'name_key' => ['bail', 'required', 'string', 'max:255'],
            'type' => ['bail', 'required', 'string', 'max:100', 'regex:/^(?=.*[A-Za-z])[A-Za-z0-9\s\-\/]{2,100}$/'],
            'location' => ['bail', 'nullable', 'string', 'max:255'],
            'country' => ['bail', 'nullable', 'string', 'max:100'],
            'contact' => ['bail', 'nullable', 'string', 'max:20', 'regex:'.PhoneNormalizer::TZ_REGEX],
            'is_active' => ['bail', 'sometimes', 'boolean'],
        ];
    }
}
