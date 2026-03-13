<?php

namespace App\Policies;

use App\Models\People\Member;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MemberPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('members.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view members.');
    }

    public function view(User $user, Member $member): Response
    {
        return $user->can('members.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view members.');
    }

    public function create(User $user): Response
    {
        return $user->can('members.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create members.');
    }

    public function update(User $user, Member $member): Response
    {
        return $user->can('members.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update members.');
    }

    public function delete(User $user, Member $member): Response
    {
        return $user->can('members.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete members.');
    }
}
