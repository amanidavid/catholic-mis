<?php

namespace App\Policies;

use App\Models\Leadership\JumuiyaLeadershipRole;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class JumuiyaLeadershipRolePolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('jumuiya-leadership-roles.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view leadership roles.');
    }

    public function view(User $user, JumuiyaLeadershipRole $jumuiyaLeadershipRole): Response
    {
        return $user->can('jumuiya-leadership-roles.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view leadership roles.');
    }

    public function create(User $user): Response
    {
        return $user->can('jumuiya-leadership-roles.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create leadership roles.');
    }

    public function update(User $user, JumuiyaLeadershipRole $jumuiyaLeadershipRole): Response
    {
        return $user->can('jumuiya-leadership-roles.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update leadership roles.');
    }

    public function delete(User $user, JumuiyaLeadershipRole $jumuiyaLeadershipRole): Response
    {
        return $user->can('jumuiya-leadership-roles.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete leadership roles.');
    }
}
