<?php

namespace App\Policies;

use App\Models\ParishStaffAssignmentRole;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ParishStaffAssignmentRolePolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('parish-staff-positions.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view staff positions.');
    }

    public function create(User $user): Response
    {
        return $user->can('parish-staff-positions.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create staff positions.');
    }

    public function update(User $user, ParishStaffAssignmentRole $role): Response
    {
        return $user->can('parish-staff-positions.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update staff positions.');
    }

    public function delete(User $user, ParishStaffAssignmentRole $role): Response
    {
        return $user->can('parish-staff-positions.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete staff positions.');
    }
}
