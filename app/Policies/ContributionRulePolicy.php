<?php

namespace App\Policies;

use App\Models\Finance\ContributionRule;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ContributionRulePolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('contributions.rules.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view contribution rules.');
    }

    public function view(User $user, ContributionRule $rule): Response
    {
        return $user->can('contributions.rules.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view contribution rules.');
    }

    public function create(User $user): Response
    {
        return $user->can('contributions.rules.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create contribution rules.');
    }

    public function update(User $user, ContributionRule $rule): Response
    {
        return $user->can('contributions.rules.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update contribution rules.');
    }

    public function delete(User $user, ContributionRule $rule): Response
    {
        return $user->can('contributions.rules.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete contribution rules.');
    }
}
