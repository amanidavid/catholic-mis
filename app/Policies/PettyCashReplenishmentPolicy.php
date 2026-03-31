<?php

namespace App\Policies;

use App\Models\Finance\PettyCashReplenishment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PettyCashReplenishmentPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('finance.petty-cash-replenishments.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view petty cash replenishments.');
    }

    public function view(User $user, PettyCashReplenishment $replenishment): Response
    {
        return $user->can('finance.petty-cash-replenishments.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view petty cash replenishments.');
    }

    public function create(User $user): Response
    {
        return $user->can('finance.petty-cash-replenishments.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create petty cash replenishments.');
    }

    public function submit(User $user, PettyCashReplenishment $replenishment): Response
    {
        return $user->can('finance.petty-cash-replenishments.create')
            && $replenishment->status === 'draft'
            && (int) $replenishment->created_by === (int) $user->id
            ? Response::allow()
            : Response::deny('You do not have permission to submit this petty cash replenishment.');
    }

    public function approve(User $user, PettyCashReplenishment $replenishment): Response
    {
        return $user->can('finance.petty-cash-replenishments.approve')
            && $replenishment->status === 'submitted'
            && (int) $replenishment->created_by !== (int) $user->id
            ? Response::allow()
            : Response::deny('You do not have permission to approve petty cash replenishments.');
    }

    public function reject(User $user, PettyCashReplenishment $replenishment): Response
    {
        return $user->can('finance.petty-cash-replenishments.approve')
            && $replenishment->status === 'submitted'
            && (int) $replenishment->created_by !== (int) $user->id
            ? Response::allow()
            : Response::deny('You do not have permission to reject petty cash replenishments.');
    }

    public function post(User $user, PettyCashReplenishment $replenishment): Response
    {
        return $user->can('finance.petty-cash-replenishments.post')
            && $replenishment->status === 'approved'
            ? Response::allow()
            : Response::deny('You do not have permission to post petty cash replenishments.');
    }

    public function cancel(User $user, PettyCashReplenishment $replenishment): Response
    {
        return $user->can('finance.petty-cash-replenishments.cancel')
            && in_array($replenishment->status, ['draft', 'submitted', 'approved'], true)
            ? Response::allow()
            : Response::deny('You do not have permission to cancel petty cash replenishments.');
    }
}
