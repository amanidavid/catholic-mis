<?php

namespace App\Policies;

use App\Models\Finance\DoubleEntry;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DoubleEntryPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('finance.double-entries.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view double entry mappings.');
    }

    public function view(User $user, DoubleEntry $mapping): Response
    {
        return $user->can('finance.double-entries.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view double entry mappings.');
    }

    public function create(User $user): Response
    {
        return $user->can('finance.double-entries.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create double entry mappings.');
    }

    public function update(User $user, DoubleEntry $mapping): Response
    {
        return $user->can('finance.double-entries.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update double entry mappings.');
    }

    public function delete(User $user, DoubleEntry $mapping): Response
    {
        return $user->can('finance.double-entries.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete double entry mappings.');
    }
}
