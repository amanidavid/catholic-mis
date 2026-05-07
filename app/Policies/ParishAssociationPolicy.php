<?php

namespace App\Policies;

use App\Models\Kitume\ParishAssociation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\Response;

class ParishAssociationPolicy
{
    private const VIEW_PERMISSION = 'parish-associations.view';
    private const VIEW_ALL_PERMISSION = 'parish-associations.view-all';
    private const CREATE_PERMISSION = 'parish-associations.create';
    private const UPDATE_PERMISSION = 'parish-associations.update';
    private const DELETE_PERMISSION = 'parish-associations.delete';

    private function hasFullKitumeAccess(User $user): bool
    {
        return $user->can(self::VIEW_ALL_PERMISSION)
            || $user->can('permissions.manage');
    }

    private function canAccessAssociation(User $user, int $associationId): bool
    {
        if ($this->hasFullKitumeAccess($user)) {
            return true;
        }

        $memberId = (int) ($user->member_id ?? 0);
        if ($memberId <= 0) {
            return false;
        }

        $today = now()->toDateString();

        return DB::table('parish_association_leaderships')
            ->where('parish_association_id', $associationId)
            ->where('member_id', $memberId)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
            })
            ->exists();
    }

    public function viewAny(User $user): Response
    {
        return $user->can(self::VIEW_PERMISSION)
            ? Response::allow()
            : Response::deny('You do not have permission to view kitume groups.');
    }

    public function view(User $user, ParishAssociation $parishAssociation): Response
    {
        return $user->can(self::VIEW_PERMISSION) && $this->canAccessAssociation($user, (int) $parishAssociation->id)
            ? Response::allow()
            : Response::deny('You do not have permission to view this kitume group.');
    }

    public function create(User $user): Response
    {
        return $user->can(self::CREATE_PERMISSION)
            ? Response::allow()
            : Response::deny('You do not have permission to create kitume groups.');
    }

    public function update(User $user, ParishAssociation $parishAssociation): Response
    {
        return $user->can(self::UPDATE_PERMISSION)
            ? Response::allow()
            : Response::deny('You do not have permission to update kitume groups.');
    }

    public function delete(User $user, ParishAssociation $parishAssociation): Response
    {
        return $user->can(self::DELETE_PERMISSION)
            ? Response::allow()
            : Response::deny('You do not have permission to delete kitume groups.');
    }
}
