<?php

namespace App\Policies;

use App\Models\Finance\Bank;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BankPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('finance.banks.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view banks.');
    }

    public function view(User $user, Bank $bank): Response
    {
        return $user->can('finance.banks.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view banks.');
    }

    public function create(User $user): Response
    {
        return $user->can('finance.banks.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create banks.');
    }

    public function update(User $user, Bank $bank): Response
    {
        return $user->can('finance.banks.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update banks.');
    }

    public function delete(User $user, Bank $bank): Response
    {
        return $user->can('finance.banks.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete banks.');
    }
}
