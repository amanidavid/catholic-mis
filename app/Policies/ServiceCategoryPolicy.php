<?php

namespace App\Policies;

use App\Models\Pastoral\PastoralServiceCategory;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ServiceCategoryPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('service-categories.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view service categories.');
    }

    public function view(User $user, PastoralServiceCategory $category): Response
    {
        return $user->can('service-categories.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view service categories.');
    }

    public function create(User $user): Response
    {
        return $user->can('service-categories.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create service categories.');
    }

    public function update(User $user, PastoralServiceCategory $category): Response
    {
        return $user->can('service-categories.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update service categories.');
    }

    public function delete(User $user, PastoralServiceCategory $category): Response
    {
        return $user->can('service-categories.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete service categories.');
    }
}
