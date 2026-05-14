<?php

namespace App\Http\Requests\Pastoral;

use Illuminate\Foundation\Http\FormRequest;

class IndexServiceRequestsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('service-requests.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:all,draft,submitted,in_progress,completed,cancelled'],
            'jumuiya_uuid' => ['nullable', 'uuid'],
            'category_uuid' => ['nullable', 'uuid'],
            'date_filter' => ['nullable', 'in:all,today,this_week,this_month,custom'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
