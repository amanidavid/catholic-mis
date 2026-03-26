<?php

namespace App\Policies;

use App\Models\Finance\AccountType;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AccountTypePolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('chart-of-accounts.types.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view account types.');
    }

    public function view(User $user, AccountType $type): Response
    {
        return $user->can('chart-of-accounts.types.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view account types.');
    }

    public function create(User $user): Response
    {
        return $user->can('chart-of-accounts.types.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create account types.');
    }

    public function update(User $user, AccountType $type): Response
    {
        return $user->can('chart-of-accounts.types.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update account types.');
    }

    public function delete(User $user, AccountType $type): Response
    {
        return $user->can('chart-of-accounts.types.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete account types.');
    }
}
