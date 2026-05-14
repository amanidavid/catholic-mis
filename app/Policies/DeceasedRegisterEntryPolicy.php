<?php

namespace App\Policies;

use App\Models\Pastoral\DeceasedRegisterEntry;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DeceasedRegisterEntryPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('deceased-register.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view deceased register entries.');
    }

    public function view(User $user, DeceasedRegisterEntry $entry): Response
    {
        return $user->can('deceased-register.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view this deceased register entry.');
    }

    public function create(User $user): Response
    {
        return $user->can('deceased-register.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create deceased register entries.');
    }

    public function update(User $user, DeceasedRegisterEntry $entry): Response
    {
        return $user->can('deceased-register.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update deceased register entries.');
    }

    public function delete(User $user, DeceasedRegisterEntry $entry): Response
    {
        return $user->can('deceased-register.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete deceased register entries.');
    }
}
