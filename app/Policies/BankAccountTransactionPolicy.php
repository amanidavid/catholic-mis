<?php

namespace App\Policies;

use App\Models\Finance\BankAccountTransaction;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BankAccountTransactionPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('finance.bank-account-transactions.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view bank account transactions.');
    }

    public function view(User $user, BankAccountTransaction $transaction): Response
    {
        return $user->can('finance.bank-account-transactions.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view bank account transactions.');
    }

    public function create(User $user): Response
    {
        return $user->can('finance.bank-account-transactions.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create bank account transactions.');
    }

    public function update(User $user, BankAccountTransaction $transaction): Response
    {
        return $user->can('finance.bank-account-transactions.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update bank account transactions.');
    }

    public function delete(User $user, BankAccountTransaction $transaction): Response
    {
        return $user->can('finance.bank-account-transactions.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete bank account transactions.');
    }
}
