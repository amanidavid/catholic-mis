<?php

namespace App\Policies;

use App\Models\Finance\ContributionCatalog;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ContributionCatalogPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('contributions.catalogs.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view contribution catalogs.');
    }

    public function view(User $user, ContributionCatalog $catalog): Response
    {
        return $user->can('contributions.catalogs.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view contribution catalogs.');
    }

    public function create(User $user): Response
    {
        return $user->can('contributions.catalogs.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create contribution catalogs.');
    }

    public function update(User $user, ContributionCatalog $catalog): Response
    {
        return $user->can('contributions.catalogs.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update contribution catalogs.');
    }

    public function delete(User $user, ContributionCatalog $catalog): Response
    {
        return $user->can('contributions.catalogs.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete contribution catalogs.');
    }
}
