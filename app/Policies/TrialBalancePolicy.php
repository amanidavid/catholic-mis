<?php

namespace App\Policies;

use App\Models\Finance\TrialBalance;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TrialBalancePolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('finance.trial-balance.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view the trial balance.');
    }

    public function view(User $user, TrialBalance $trialBalance): Response
    {
        return $user->can('finance.trial-balance.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view the trial balance.');
    }
}
