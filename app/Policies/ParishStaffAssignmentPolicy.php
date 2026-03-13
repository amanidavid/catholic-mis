<?php

namespace App\Policies;

use App\Models\ParishStaffAssignment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ParishStaffAssignmentPolicy
{
    public function create(User $user): Response
    {
        return $user->can('parish-staff.assignments.manage')
            ? Response::allow()
            : Response::deny('You do not have permission to manage staff assignments.');
    }

    public function update(User $user, ParishStaffAssignment $assignment): Response
    {
        return $user->can('parish-staff.assignments.manage')
            ? Response::allow()
            : Response::deny('You do not have permission to manage staff assignments.');
    }

    public function delete(User $user, ParishStaffAssignment $assignment): Response
    {
        return $user->can('parish-staff.assignments.manage')
            ? Response::allow()
            : Response::deny('You do not have permission to manage staff assignments.');
    }
}
