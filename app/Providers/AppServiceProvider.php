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
use App\Policies\FamilyPolicy;
use App\Policies\FamilyRelationshipPolicy;
use App\Policies\JumuiyaLeadershipPolicy;
use App\Policies\JumuiyaLeadershipRolePolicy;
use App\Policies\JumuiyaPolicy;
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

        Permission::observe(PermissionObserver::class);

        Vite::prefetch(concurrency: 3);
    }
}
