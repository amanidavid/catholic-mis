<?php

namespace App\Policies;

use App\Models\Finance\BankAccount;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BankAccountPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('finance.bank-accounts.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view bank accounts.');
    }

    public function view(User $user, BankAccount $bankAccount): Response
    {
        return $user->can('finance.bank-accounts.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view bank accounts.');
    }

    public function create(User $user): Response
    {
        return $user->can('finance.bank-accounts.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create bank accounts.');
    }

    public function update(User $user, BankAccount $bankAccount): Response
    {
        return $user->can('finance.bank-accounts.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update bank accounts.');
    }

    public function delete(User $user, BankAccount $bankAccount): Response
    {
        return $user->can('finance.bank-accounts.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete bank accounts.');
    }
}
