<?php

namespace App\Services\People;

use App\Models\Pastoral\DeceasedRegisterEntry;

class MemberDeceasedStatusService
{
    public function syncFromEntry(DeceasedRegisterEntry $entry): void
    {
        $member = $entry->member;
        if (! $member) {
            return;
        }

        $member->forceFill([
            'is_deceased' => true,
            'date_of_death' => $entry->date_of_death,
        ])->save();
    }

    public function clearFromEntry(DeceasedRegisterEntry $entry): void
    {
        $member = $entry->member;
        if (! $member) {
            return;
        }

        $member->forceFill([
            'is_deceased' => false,
            'date_of_death' => null,
        ])->save();
    }
}
