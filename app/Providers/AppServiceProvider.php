<?php

namespace App\Providers;

use App\Observers\PermissionObserver;
use App\Models\Structure\Diocese;
use App\Models\Leadership\JumuiyaLeadership;
use App\Models\Leadership\JumuiyaLeadershipRole;
use App\Models\People\Family;
use App\Models\People\FamilyRelationship;
use App\Models\People\Member;
use App\Models\AuditLog;
use App\Models\Attendance\JumuiyaWeeklyMeeting;
use App\Models\Structure\Jumuiya;
use App\Models\Structure\Parish;
use App\Models\Structure\Zone;
use App\Models\ParishStaff;
use App\Models\ParishStaffAssignmentRole;
use App\Models\Finance\AccountGroup;
use App\Models\Finance\AccountSubtype;
use App\Models\Finance\AccountType;
use App\Models\Finance\Bank;
use App\Models\Finance\BankAccount;
use App\Models\Finance\BankAccountTransaction;
use App\Models\Finance\DoubleEntry;
use App\Models\Finance\GeneralLedger;
use App\Models\Finance\Journal;
use App\Models\Finance\Ledger;
use App\Models\Finance\PettyCashFund;
use App\Models\Finance\PettyCashReplenishment;
use App\Models\Finance\PettyCashVoucher;
use App\Models\Finance\TrialBalance;
use App\Policies\FamilyPolicy;
use App\Policies\FamilyRelationshipPolicy;
use App\Policies\JumuiyaLeadershipPolicy;
use App\Policies\JumuiyaLeadershipRolePolicy;
use App\Policies\JumuiyaPolicy;
use App\Policies\AccountGroupPolicy;
use App\Policies\AccountSubtypePolicy;
use App\Policies\AccountTypePolicy;
use App\Policies\BankAccountPolicy;
use App\Policies\BankAccountTransactionPolicy;
use App\Policies\BankPolicy;
use App\Policies\DoubleEntryPolicy;
use App\Policies\GeneralLedgerPolicy;
use App\Policies\JournalPolicy;
use App\Policies\LedgerPolicy;
use App\Policies\PettyCashFundPolicy;
use App\Policies\PettyCashReplenishmentPolicy;
use App\Policies\PettyCashVoucherPolicy;
use App\Policies\TrialBalancePolicy;
use App\Policies\MemberPolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\ParishStaffPolicy;
use App\Policies\ParishStaffAssignmentRolePolicy;
use App\Policies\JumuiyaWeeklyMeetingPolicy;
use App\Policies\SetupPolicy;
use App\Policies\ZonePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Diocese::class, SetupPolicy::class);
        Gate::policy(Parish::class, SetupPolicy::class);
        Gate::policy(Zone::class, ZonePolicy::class);
        Gate::policy(Jumuiya::class, JumuiyaPolicy::class);
        Gate::policy(JumuiyaLeadership::class, JumuiyaLeadershipPolicy::class);
        Gate::policy(JumuiyaLeadershipRole::class, JumuiyaLeadershipRolePolicy::class);
        Gate::policy(Family::class, FamilyPolicy::class);
        Gate::policy(FamilyRelationship::class, FamilyRelationshipPolicy::class);
        Gate::policy(Member::class, MemberPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(JumuiyaWeeklyMeeting::class, JumuiyaWeeklyMeetingPolicy::class);
        Gate::policy(ParishStaff::class, ParishStaffPolicy::class);
        Gate::policy(ParishStaffAssignmentRole::class, ParishStaffAssignmentRolePolicy::class);

        Gate::policy(AccountGroup::class, AccountGroupPolicy::class);
        Gate::policy(AccountType::class, AccountTypePolicy::class);
        Gate::policy(AccountSubtype::class, AccountSubtypePolicy::class);
        Gate::policy(Ledger::class, LedgerPolicy::class);
        Gate::policy(Journal::class, JournalPolicy::class);
        Gate::policy(GeneralLedger::class, GeneralLedgerPolicy::class);
        Gate::policy(PettyCashFund::class, PettyCashFundPolicy::class);
        Gate::policy(PettyCashVoucher::class, PettyCashVoucherPolicy::class);
        Gate::policy(PettyCashReplenishment::class, PettyCashReplenishmentPolicy::class);
        Gate::policy(TrialBalance::class, TrialBalancePolicy::class);
        Gate::policy(DoubleEntry::class, DoubleEntryPolicy::class);
        Gate::policy(Bank::class, BankPolicy::class);
        Gate::policy(BankAccount::class, BankAccountPolicy::class);
        Gate::policy(BankAccountTransaction::class, BankAccountTransactionPolicy::class);

        Permission::observe(PermissionObserver::class);

        Vite::prefetch(concurrency: 3);
    }
}
