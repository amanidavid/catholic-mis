<?php

namespace App\Policies;

use App\Models\Finance\Ledger;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LedgerPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('chart-of-accounts.ledgers.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view ledgers.');
    }

    public function view(User $user, Ledger $ledger): Response
    {
        return $user->can('chart-of-accounts.ledgers.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view ledgers.');
    }

    public function create(User $user): Response
    {
        return $user->can('chart-of-accounts.ledgers.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create ledgers.');
    }

    public function update(User $user, Ledger $ledger): Response
    {
        return $user->can('chart-of-accounts.ledgers.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update ledgers.');
    }

    public function delete(User $user, Ledger $ledger): Response
    {
        return $user->can('chart-of-accounts.ledgers.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete ledgers.');
    }
}
