<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class BulkMarkWeeklyAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('weekly-attendance.record');
    }

    public function rules(): array
    {
        return [
            'member_uuids' => ['required', 'array', 'min:1'],
            'member_uuids.*' => ['required', 'string', 'uuid'],
            'status' => ['required', 'string', 'in:present,absent,sick,travel,other'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
