<?php

namespace App\Policies;

use App\Models\Finance\ContributionPaymentRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ContributionPaymentRequestPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('contributions.obligations.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view contribution payment requests.');
    }

    public function view(User $user, ContributionPaymentRequest $paymentRequest): Response
    {
        return $user->can('contributions.obligations.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view contribution payment requests.');
    }

    public function create(User $user): Response
    {
        return $user->can('contributions.obligations.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create contribution payment requests.');
    }

    public function update(User $user, ContributionPaymentRequest $paymentRequest): Response
    {
        return $user->can('contributions.obligations.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update contribution payment requests.');
    }

    public function delete(User $user, ContributionPaymentRequest $paymentRequest): Response
    {
        return $user->can('contributions.obligations.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete contribution payment requests.');
    }
}
