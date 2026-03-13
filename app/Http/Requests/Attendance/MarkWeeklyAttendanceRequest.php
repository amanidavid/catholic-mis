<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class MarkWeeklyAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('weekly-attendance.record') ?? false;
    }

    public function rules(): array
    {
        return [
            'member_uuid' => ['required', 'uuid'],
            'status' => ['required', 'string', 'in:present,absent,sick,travel,other'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
