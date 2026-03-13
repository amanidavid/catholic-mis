<?php

namespace App\Policies;

use App\Models\ParishStaff;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ParishStaffPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('parish-staff.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view parish staff.');
    }

    public function view(User $user, ParishStaff $staff): Response
    {
        return $user->can('parish-staff.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view parish staff.');
    }

    public function create(User $user): Response
    {
        return $user->can('parish-staff.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create parish staff.');
    }

    public function update(User $user, ParishStaff $staff): Response
    {
        return $user->can('parish-staff.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update parish staff.');
    }

    public function delete(User $user, ParishStaff $staff): Response
    {
        return $user->can('parish-staff.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete parish staff.');
    }

    public function manageAssignments(User $user, ParishStaff $staff): Response
    {
        return $user->can('parish-staff.assignments.manage')
            ? Response::allow()
            : Response::deny('You do not have permission to manage staff assignments.');
    }

    public function manageLogin(User $user, ParishStaff $staff): Response
    {
        return $user->can('parish-staff.login.manage')
            ? Response::allow()
            : Response::deny('You do not have permission to manage staff login.');
    }
}
