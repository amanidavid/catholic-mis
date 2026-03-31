<?php

namespace App\Policies;

use App\Models\Finance\PettyCashVoucher;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PettyCashVoucherPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('finance.petty-cash-vouchers.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view petty cash vouchers.');
    }

    public function view(User $user, PettyCashVoucher $voucher): Response
    {
        return $user->can('finance.petty-cash-vouchers.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view petty cash vouchers.');
    }

    public function create(User $user): Response
    {
        return $user->can('finance.petty-cash-vouchers.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create petty cash vouchers.');
    }

    public function update(User $user, PettyCashVoucher $voucher): Response
    {
        return $user->can('finance.petty-cash-vouchers.update')
            && $voucher->status === 'draft'
            && (int) $voucher->created_by === (int) $user->id
            ? Response::allow()
            : Response::deny('You do not have permission to edit this petty cash draft.');
    }

    public function submit(User $user, PettyCashVoucher $voucher): Response
    {
        return $user->can('finance.petty-cash-vouchers.create')
            && $voucher->status === 'draft'
            && (int) $voucher->created_by === (int) $user->id
            ? Response::allow()
            : Response::deny('You do not have permission to submit petty cash vouchers.');
    }

    public function approve(User $user, PettyCashVoucher $voucher): Response
    {
        return $user->can('finance.petty-cash-vouchers.approve')
            && $voucher->status === 'submitted'
            && (int) $voucher->created_by !== (int) $user->id
            ? Response::allow()
            : Response::deny('You do not have permission to approve petty cash vouchers.');
    }

    public function reject(User $user, PettyCashVoucher $voucher): Response
    {
        return $user->can('finance.petty-cash-vouchers.approve')
            && $voucher->status === 'submitted'
            && (int) $voucher->created_by !== (int) $user->id
            ? Response::allow()
            : Response::deny('You do not have permission to reject petty cash vouchers.');
    }

    public function post(User $user, PettyCashVoucher $voucher): Response
    {
        return $user->can('finance.petty-cash-vouchers.post')
            && $voucher->status === 'approved'
            ? Response::allow()
            : Response::deny('You do not have permission to post petty cash vouchers.');
    }

    public function cancel(User $user, PettyCashVoucher $voucher): Response
    {
        return $user->can('finance.petty-cash-vouchers.cancel')
            && in_array($voucher->status, ['draft', 'submitted', 'approved'], true)
            ? Response::allow()
            : Response::deny('You do not have permission to cancel petty cash vouchers.');
    }
}
