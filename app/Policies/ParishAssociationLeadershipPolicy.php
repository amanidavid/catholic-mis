<?php

namespace App\Policies;

use App\Models\Kitume\ParishAssociationLeadership;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class ParishAssociationLeadershipPolicy
{
    private const VIEW_PERMISSION = 'parish-associations.leadership.view';
    private const VIEW_ALL_PERMISSION = 'parish-associations.view-all';
    private const CREATE_PERMISSION = 'parish-associations.leadership.create';
    private const UPDATE_PERMISSION = 'parish-associations.leadership.update';
    private const DELETE_PERMISSION = 'parish-associations.leadership.delete';

    private function hasFullKitumeAccess(User $user): bool
    {
        return $user->can(self::VIEW_ALL_PERMISSION) || $user->can('permissions.manage');
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
            : Response::deny('You do not have permission to view kitume leadership.');
    }

    public function view(User $user, ParishAssociationLeadership $leadership): Response
    {
        return $user->can(self::VIEW_PERMISSION) && $this->canAccessAssociation($user, (int) $leadership->parish_association_id)
            ? Response::allow()
            : Response::deny('You do not have permission to view this kitume leadership record.');
    }

    public function create(User $user): Response
    {
        return $user->can(self::CREATE_PERMISSION)
            ? Response::allow()
            : Response::deny('You do not have permission to assign kitume leadership.');
    }

    public function update(User $user, ParishAssociationLeadership $leadership): Response
    {
        return $user->can(self::UPDATE_PERMISSION)
            ? Response::allow()
            : Response::deny('You do not have permission to update kitume leadership.');
    }

    public function delete(User $user, ParishAssociationLeadership $leadership): Response
    {
        return $user->can(self::DELETE_PERMISSION)
            ? Response::allow()
            : Response::deny('You do not have permission to delete kitume leadership.');
    }
}
