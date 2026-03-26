<?php

namespace App\Policies;

use App\Models\Finance\AccountGroup;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AccountGroupPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('chart-of-accounts.groups.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view account groups.');
    }

    public function view(User $user, AccountGroup $group): Response
    {
        return $user->can('chart-of-accounts.groups.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view account groups.');
    }

    public function create(User $user): Response
    {
        return $user->can('chart-of-accounts.groups.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create account groups.');
    }

    public function update(User $user, AccountGroup $group): Response
    {
        return $user->can('chart-of-accounts.groups.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update account groups.');
    }

    public function delete(User $user, AccountGroup $group): Response
    {
        return $user->can('chart-of-accounts.groups.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete account groups.');
    }
}
