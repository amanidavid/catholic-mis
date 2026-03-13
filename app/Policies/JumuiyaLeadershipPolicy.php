<?php

namespace App\Policies;

use App\Models\Leadership\JumuiyaLeadership;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class JumuiyaLeadershipPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('jumuiya-leaderships.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view leadership assignments.');
    }

    public function view(User $user, JumuiyaLeadership $jumuiyaLeadership): Response
    {
        return $user->can('jumuiya-leaderships.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view leadership assignments.');
    }

    public function create(User $user): Response
    {
        return $user->can('jumuiya-leaderships.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create leadership assignments.');
    }

    public function update(User $user, JumuiyaLeadership $jumuiyaLeadership): Response
    {
        return $user->can('jumuiya-leaderships.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update leadership assignments.');
    }

    public function delete(User $user, JumuiyaLeadership $jumuiyaLeadership): Response
    {
        return $user->can('jumuiya-leaderships.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete leadership assignments.');
    }
}
