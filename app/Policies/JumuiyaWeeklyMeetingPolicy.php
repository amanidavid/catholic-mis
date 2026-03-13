<?php

namespace App\Policies;

use App\Models\Attendance\JumuiyaWeeklyMeeting;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class JumuiyaWeeklyMeetingPolicy
{
    protected function isScopedToMeetingJumuiya(User $user, JumuiyaWeeklyMeeting $meeting): bool
    {
        if ($user->can('jumuiyas.view')) {
            return true;
        }

        $userJumuiyaId = (int) ($user->member?->jumuiya_id ?? 0);

        return $userJumuiyaId > 0 && $userJumuiyaId === (int) $meeting->jumuiya_id;
    }

    public function viewAny(User $user): Response
    {
        return $user->can('weekly-attendance.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view weekly attendance.');
    }

    public function view(User $user, JumuiyaWeeklyMeeting $meeting): Response
    {
        if (! $user->can('weekly-attendance.view')) {
            return Response::deny('You do not have permission to view weekly attendance.');
        }

        return $this->isScopedToMeetingJumuiya($user, $meeting)
            ? Response::allow()
            : Response::deny('You do not have permission to access weekly attendance for this Christian Community.');
    }

    public function create(User $user): Response
    {
        return $user->can('weekly-attendance.record')
            ? Response::allow()
            : Response::deny('You do not have permission to record weekly attendance.');
    }

    public function update(User $user, JumuiyaWeeklyMeeting $meeting): Response
    {
        if (! $user->can('weekly-attendance.edit')) {
            return Response::deny('You do not have permission to edit weekly attendance.');
        }

        return $this->isScopedToMeetingJumuiya($user, $meeting)
            ? Response::allow()
            : Response::deny('You do not have permission to edit weekly attendance for this Christian Community.');
    }

    public function overrideLock(User $user, JumuiyaWeeklyMeeting $meeting): Response
    {
        if (! $user->can('weekly-attendance.override-lock')) {
            return Response::deny('You do not have permission to override weekly attendance lock.');
        }

        return $this->isScopedToMeetingJumuiya($user, $meeting)
            ? Response::allow()
            : Response::deny('You do not have permission to override weekly attendance lock for this Christian Community.');
    }
}
