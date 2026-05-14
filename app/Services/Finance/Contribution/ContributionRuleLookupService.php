<?php

namespace App\Services\Finance\Contribution;

use App\Models\Finance\ContributionRule;
use Carbon\Carbon;

class ContributionRuleLookupService
{
    /**
     * Find active contribution rules for a source type and optional ID.
     *
     * @return array<int, ContributionRule>
     */
    public function findRulesForSource(int $parishId, string $appliesToType, ?int $appliesToId = null): array
    {
        $query = ContributionRule::query()
            ->where('parish_id', $parishId)
            ->where('applies_to_type', $appliesToType)
            ->where('is_active', true)
            ->where(function ($q) {
                $today = Carbon::now()->toDateString();
                $q->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $today);
            })
            ->where(function ($q) {
                $today = Carbon::now()->toDateString();
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $today);
            })
            ->with('catalog:id,uuid,name,code')
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($appliesToId !== null) {
            $query->where('applies_to_id', $appliesToId);
        } else {
            $query->whereNull('applies_to_id');
        }

        return $query->get()->all();
    }

    /**
     * Find rules for pastoral service category.
     *
     * @return array<int, ContributionRule>
     */
    public function findRulesForPastoralServiceCategory(int $parishId, int $serviceCategoryId): array
    {
        return $this->findRulesForSource($parishId, 'pastoral_service_category', $serviceCategoryId);
    }

    /**
     * Find rules for baptism.
     *
     * @return array<int, ContributionRule>
     */
    public function findRulesForBaptism(int $parishId, int $baptismId): array
    {
        return $this->findRulesForSource($parishId, 'baptism', $baptismId);
    }

    /**
     * Find rules for marriage.
     *
     * @return array<int, ContributionRule>
     */
    public function findRulesForMarriage(int $parishId, int $marriageId): array
    {
        return $this->findRulesForSource($parishId, 'marriage', $marriageId);
    }

    /**
     * Find rules for program cycle.
     *
     * @return array<int, ContributionRule>
     */
    public function findRulesForProgramCycle(int $parishId, int $cycleId): array
    {
        return $this->findRulesForSource($parishId, 'program_cycle', $cycleId);
    }
}
