<?php

namespace App\Policies;

use App\Models\Finance\TrialBalance;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PettyCashBookPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('finance.petty-cash-book.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view the petty cash book.');
    }

    public function view(User $user, TrialBalance $report): Response
    {
        return $user->can('finance.petty-cash-book.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view the petty cash book.');
    }
}
