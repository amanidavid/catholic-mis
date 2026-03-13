<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class OpenWeeklyMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('weekly-attendance.record') ?? false;
    }

    public function rules(): array
    {
        return [
            'meeting_date' => ['required', 'date'],
            'jumuiya_uuid' => ['nullable', 'uuid'],
        ];
    }
}
