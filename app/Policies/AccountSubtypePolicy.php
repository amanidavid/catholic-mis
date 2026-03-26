<?php

namespace App\Policies;

use App\Models\Finance\AccountSubtype;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AccountSubtypePolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('chart-of-accounts.subtypes.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view account subtypes.');
    }

    public function view(User $user, AccountSubtype $subtype): Response
    {
        return $user->can('chart-of-accounts.subtypes.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view account subtypes.');
    }

    public function create(User $user): Response
    {
        return $user->can('chart-of-accounts.subtypes.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create account subtypes.');
    }

    public function update(User $user, AccountSubtype $subtype): Response
    {
        return $user->can('chart-of-accounts.subtypes.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update account subtypes.');
    }

    public function delete(User $user, AccountSubtype $subtype): Response
    {
        return $user->can('chart-of-accounts.subtypes.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete account subtypes.');
    }
}
