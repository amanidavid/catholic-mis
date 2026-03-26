<?php

namespace App\Policies;

use App\Models\Finance\Journal;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class JournalPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('finance.journals.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view journals.');
    }

    public function view(User $user, Journal $journal): Response
    {
        return $user->can('finance.journals.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view journals.');
    }

    public function create(User $user): Response
    {
        return $user->can('finance.journals.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create journals.');
    }

    public function post(User $user, Journal $journal): Response
    {
        return $user->can('finance.journals.post')
            ? Response::allow()
            : Response::deny('You do not have permission to post journals.');
    }
}
