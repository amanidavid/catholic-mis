<?php

namespace App\Policies;

use App\Models\Finance\GeneralLedger;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GeneralLedgerPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('finance.general-ledger.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view the general ledger.');
    }

    public function view(User $user, GeneralLedger $entry): Response
    {
        return $user->can('finance.general-ledger.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view the general ledger.');
    }
}
