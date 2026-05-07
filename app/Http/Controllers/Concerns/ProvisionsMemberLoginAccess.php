<?php

namespace App\Http\Controllers\Concerns;

use App\Models\People\Member;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

trait ProvisionsMemberLoginAccess
{
    protected function provisionMemberLoginAccess(Member $member, int $parishId, array $directPermissions = [], ?string $userCategory = null): ?string
    {
        $tempPassword = null;
        $email = trim((string) ($member->email ?? ''));
        if ($email === '') {
            throw new RuntimeException('Member email is required to create a login account.');
        }

        $permissions = collect($directPermissions)
            ->filter(fn ($permission) => is_string($permission) && trim($permission) !== '')
            ->map(fn ($permission) => trim($permission))
            ->unique()
            ->values()
            ->all();

        $existingByMember = User::query()->where('member_id', $member->id)->first();
        if ($existingByMember) {
            $existingByMember->forceFill([
                'email' => $email,
                'parish_id' => $parishId,
                'user_category' => $userCategory ?? $existingByMember->user_category,
            ]);

            if (! $existingByMember->is_active) {
                $tempPassword = Str::password(12);
                $existingByMember->forceFill([
                    'is_active' => true,
                    'must_change_password' => true,
                    'password' => Hash::make($tempPassword),
                ]);
            }

            $existingByMember->save();

            if (! empty($permissions)) {
                $existingByMember->givePermissionTo($permissions);
            }

            return $tempPassword;
        }

        $existingByEmail = User::query()->where('email', $email)->first();
        if ($existingByEmail) {
            if ($existingByEmail->member_id && (int) $existingByEmail->member_id !== (int) $member->id) {
                throw new RuntimeException('This email is already used by another account.');
            }

            $existingByEmail->forceFill([
                'member_id' => $member->id,
                'parish_id' => $parishId,
                'user_category' => $userCategory ?? $existingByEmail->user_category,
            ]);

            if (! $existingByEmail->is_active) {
                $tempPassword = Str::password(12);
                $existingByEmail->forceFill([
                    'is_active' => true,
                    'must_change_password' => true,
                    'password' => Hash::make($tempPassword),
                ]);
            }

            $existingByEmail->save();

            if (! empty($permissions)) {
                $existingByEmail->givePermissionTo($permissions);
            }

            return $tempPassword;
        }

        $tempPassword = Str::password(12);
        $fullName = trim(implode(' ', array_filter([
            $member->first_name,
            $member->middle_name,
            $member->last_name,
        ])));

        $user = User::create([
            'name' => $fullName !== '' ? $fullName : $email,
            'email' => $email,
            'password' => Hash::make($tempPassword),
            'member_id' => $member->id,
            'parish_id' => $parishId,
            'user_category' => $userCategory ?? 'member',
            'is_active' => true,
            'must_change_password' => true,
        ]);

        if (! empty($permissions)) {
            $user->givePermissionTo($permissions);
        }

        return $tempPassword;
    }

    protected function syncMemberDirectPermissions(Member $member, array $permissions, bool $enabled): void
    {
        $user = User::query()->where('member_id', $member->id)->first();
        if (! $user) {
            return;
        }

        $permissionList = collect($permissions)
            ->filter(fn ($permission) => is_string($permission) && trim($permission) !== '')
            ->map(fn ($permission) => trim($permission))
            ->unique()
            ->values()
            ->all();

        if (empty($permissionList)) {
            return;
        }

        if ($enabled) {
            $user->givePermissionTo($permissionList);
            return;
        }

        foreach ($permissionList as $permission) {
            if ($user->hasDirectPermission($permission)) {
                $user->revokePermissionTo($permission);
            }
        }
    }
}
