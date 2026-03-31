<?php

namespace App\Policies;

use App\Models\Finance\PettyCashFund;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PettyCashFundPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('finance.petty-cash-funds.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view petty cash funds.');
    }

    public function view(User $user, PettyCashFund $fund): Response
    {
        return $user->can('finance.petty-cash-funds.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view petty cash funds.');
    }

    public function create(User $user): Response
    {
        return $user->can('finance.petty-cash-funds.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create petty cash funds.');
    }

    public function update(User $user, PettyCashFund $fund): Response
    {
        return $user->can('finance.petty-cash-funds.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update petty cash funds.');
    }
}
