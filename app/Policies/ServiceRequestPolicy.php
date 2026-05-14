<?php

namespace App\Policies;

use App\Models\Pastoral\PastoralServiceRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ServiceRequestPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('service-requests.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view service requests.');
    }

    public function view(User $user, PastoralServiceRequest $request): Response
    {
        return $user->can('service-requests.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view this service request.');
    }

    public function create(User $user): Response
    {
        return $user->can('service-requests.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create service requests.');
    }

    public function update(User $user, PastoralServiceRequest $request): Response
    {
        return $user->can('service-requests.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update service requests.');
    }

    public function delete(User $user, PastoralServiceRequest $request): Response
    {
        return $user->can('service-requests.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete service requests.');
    }

    public function schedule(User $user, PastoralServiceRequest $request): Response
    {
        return $user->can('service-requests.schedule')
            ? Response::allow()
            : Response::deny('You do not have permission to schedule service requests.');
    }
}
