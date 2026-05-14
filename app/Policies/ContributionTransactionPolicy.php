<?php

namespace App\Policies;

use App\Models\Finance\ContributionTransaction;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ContributionTransactionPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('contributions.transactions.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view contribution transactions.');
    }

    public function view(User $user, ContributionTransaction $transaction): Response
    {
        return $user->can('contributions.transactions.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view contribution transactions.');
    }

    public function create(User $user): Response
    {
        return $user->can('contributions.transactions.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create contribution transactions.');
    }

    public function update(User $user, ContributionTransaction $transaction): Response
    {
        return $user->can('contributions.transactions.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update contribution transactions.');
    }

    public function delete(User $user, ContributionTransaction $transaction): Response
    {
        return $user->can('contributions.transactions.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete contribution transactions.');
    }
}
