<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AccessControl\PermissionController;
use App\Http\Controllers\AccessControl\RoleController;
use App\Http\Controllers\AccessControl\UserAccessController;
use App\Http\Controllers\Leadership\JumuiyaLeadershipController;
use App\Http\Controllers\Leadership\JumuiyaLeadershipRoleController;
use App\Http\Controllers\People\FamilyController;
use App\Http\Controllers\People\FamilyRelationshipController;
use App\Http\Controllers\People\MemberController;
use App\Http\Controllers\Audit\AuditLogController;
use App\Http\Controllers\Attendance\WeeklyAttendanceController;
use App\Http\Controllers\Attendance\WeeklyAttendanceReportController;
use App\Http\Controllers\Jumuiyas\JumuiyaController;
use App\Http\Controllers\ParishStaff\ParishStaffController;
use App\Http\Controllers\ParishStaff\ParishStaffPositionController;
use App\Http\Controllers\Setup\SetupController;
use App\Http\Controllers\Zones\ZoneController;
use App\Http\Controllers\Clergy\InstitutionController;
use App\Models\Structure\Parish;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    if (!Parish::query()->exists()) {
        return redirect()->route('setup.index');
    }

    return Inertia::render('Dashboard', [
        'postCount' => 0,
        'recentPosts' => [],
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/setup', [SetupController::class, 'index'])->name('setup.index');
    Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');

    Route::middleware('can:permissions.manage')->prefix('access-control')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('access-control.roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('access-control.roles.store');
        Route::patch('/roles/{role}', [RoleController::class, 'update'])->name('access-control.roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('access-control.roles.destroy');
        Route::post('/roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->name('access-control.roles.permissions.sync');

        Route::get('/permissions', [PermissionController::class, 'index'])->name('access-control.permissions.index');

        Route::get('/users', [UserAccessController::class, 'index'])->name('access-control.users.index');
        Route::post('/users/{user}/roles', [UserAccessController::class, 'syncRoles'])->name('access-control.users.roles.sync');
        Route::post('/users/{user}/direct-permissions', [UserAccessController::class, 'syncDirectPermissions'])->name('access-control.users.permissions.sync');
    });

    Route::get('/zones', [ZoneController::class, 'index'])->name('zones.index');
    Route::get('/zones/lookup', [ZoneController::class, 'lookup'])->name('zones.lookup');
    Route::post('/zones', [ZoneController::class, 'store'])->name('zones.store');
    Route::patch('/zones/{zone}', [ZoneController::class, 'update'])->name('zones.update');
    Route::delete('/zones/{zone}', [ZoneController::class, 'destroy'])->name('zones.destroy');

    Route::get('/jumuiyas', [JumuiyaController::class, 'index'])->name('jumuiyas.index');
    Route::get('/jumuiyas/create', [JumuiyaController::class, 'create'])->name('jumuiyas.create');
    Route::get('/jumuiyas/lookup', [JumuiyaController::class, 'lookup'])->name('jumuiyas.lookup');
    Route::post('/jumuiyas', [JumuiyaController::class, 'store'])->name('jumuiyas.store');
    Route::patch('/jumuiyas/{jumuiya}', [JumuiyaController::class, 'update'])->name('jumuiyas.update');
    Route::delete('/jumuiyas/{jumuiya}', [JumuiyaController::class, 'destroy'])->name('jumuiyas.destroy');

    Route::post('/jumuiya-leaderships', [JumuiyaLeadershipController::class, 'store'])->name('jumuiya-leaderships.store');
    Route::patch('/jumuiya-leaderships/{jumuiyaLeadership}', [JumuiyaLeadershipController::class, 'update'])->name('jumuiya-leaderships.update');
    Route::delete('/jumuiya-leaderships/{jumuiyaLeadership}', [JumuiyaLeadershipController::class, 'destroy'])->name('jumuiya-leaderships.destroy');

    Route::get('/jumuiya-leaderships', [JumuiyaLeadershipController::class, 'index'])->name('jumuiya-leaderships.index');

    Route::post('/jumuiya-leadership-roles', [JumuiyaLeadershipRoleController::class, 'store'])->name('jumuiya-leadership-roles.store');
    Route::patch('/jumuiya-leadership-roles/{jumuiyaLeadershipRole}', [JumuiyaLeadershipRoleController::class, 'update'])->name('jumuiya-leadership-roles.update');
    Route::delete('/jumuiya-leadership-roles/{jumuiyaLeadershipRole}', [JumuiyaLeadershipRoleController::class, 'destroy'])->name('jumuiya-leadership-roles.destroy');

    Route::get('/jumuiya-leadership-roles', [JumuiyaLeadershipRoleController::class, 'index'])->name('jumuiya-leadership-roles.index');

    Route::get('/families', [FamilyController::class, 'index'])->name('families.index');
    Route::get('/jumuiyas/lookup', [JumuiyaController::class, 'lookup'])->name('jumuiyas.lookup');
    Route::get('/families/lookup', [FamilyController::class, 'lookup'])->name('families.lookup');
    Route::get('/family-relationships/lookup', [FamilyRelationshipController::class, 'lookup'])->name('family-relationships.lookup');
    Route::get('/members/lookup', [MemberController::class, 'lookup'])->name('members.lookup');
    Route::get('/institutions/lookup', [InstitutionController::class, 'lookup'])->name('institutions.lookup');

    Route::get('/institutions', [InstitutionController::class, 'index'])->name('institutions.index');
    Route::post('/institutions', [InstitutionController::class, 'store'])->name('institutions.store');
    Route::patch('/institutions/{institution}', [InstitutionController::class, 'update'])->name('institutions.update');
    Route::delete('/institutions/{institution}', [InstitutionController::class, 'destroy'])->name('institutions.destroy');

    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::get('/members/create', [MemberController::class, 'create'])->name('members.create');
    Route::get('/members/{member}/edit', [MemberController::class, 'edit'])->name('members.edit');
    Route::post('/members', [MemberController::class, 'store'])->name('members.store');
    Route::patch('/members/{member}', [MemberController::class, 'update'])->name('members.update');
    Route::post('/members/{member}/transfer', [MemberController::class, 'transfer'])->name('members.transfer');
    Route::delete('/members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');

    Route::get('/family-relationships', [FamilyRelationshipController::class, 'index'])->name('family-relationships.index');
    Route::get('/family-relationships/lookup', [FamilyRelationshipController::class, 'lookup'])->name('family-relationships.lookup');
    Route::post('/family-relationships', [FamilyRelationshipController::class, 'store'])->name('family-relationships.store');
    Route::patch('/family-relationships/{familyRelationship}', [FamilyRelationshipController::class, 'update'])->name('family-relationships.update');
    Route::delete('/family-relationships/{familyRelationship}', [FamilyRelationshipController::class, 'destroy'])->name('family-relationships.destroy');

    Route::middleware('can:audit-logs.view')->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });

    Route::prefix('weekly-attendance')->group(function () {
        Route::get('/', [WeeklyAttendanceController::class, 'index'])->name('weekly-attendance.index');
        Route::post('/open', [WeeklyAttendanceController::class, 'open'])->name('weekly-attendance.open');
        Route::post('/meetings/{meeting}/mark', [WeeklyAttendanceController::class, 'mark'])->name('weekly-attendance.meetings.mark');
        Route::post('/meetings/{meeting}/bulk-mark', [WeeklyAttendanceController::class, 'bulkMark'])->name('weekly-attendance.meetings.bulk-mark');
        Route::post('/meetings/{meeting}/close', [WeeklyAttendanceController::class, 'close'])->name('weekly-attendance.meetings.close');
        Route::get('/meetings/{meeting}/family-report', [WeeklyAttendanceController::class, 'familyReport'])->name('weekly-attendance.meetings.family-report');

        Route::prefix('reports')->group(function () {
            Route::get('/community', [WeeklyAttendanceReportController::class, 'communityIndex'])->name('weekly-attendance.reports.community');
            Route::get('/community/data', [WeeklyAttendanceReportController::class, 'communityData'])->name('weekly-attendance.reports.community.data');
            Route::get('/community/export', [WeeklyAttendanceReportController::class, 'communityExport'])->name('weekly-attendance.reports.community.export');

            Route::get('/action-list', [WeeklyAttendanceReportController::class, 'actionListIndex'])->name('weekly-attendance.reports.action-list');
            Route::get('/action-list/data', [WeeklyAttendanceReportController::class, 'actionListData'])->name('weekly-attendance.reports.action-list.data');
            Route::get('/action-list/export', [WeeklyAttendanceReportController::class, 'actionListExport'])->name('weekly-attendance.reports.action-list.export');

            Route::get('/families', [WeeklyAttendanceReportController::class, 'familyIndex'])->name('weekly-attendance.reports.families');
            Route::get('/families/data', [WeeklyAttendanceReportController::class, 'familyData'])->name('weekly-attendance.reports.families.data');
            Route::get('/families/export', [WeeklyAttendanceReportController::class, 'familyExport'])->name('weekly-attendance.reports.families.export');

            Route::get('/members', [WeeklyAttendanceReportController::class, 'memberIndex'])->name('weekly-attendance.reports.members');
            Route::get('/members/data', [WeeklyAttendanceReportController::class, 'memberData'])->name('weekly-attendance.reports.members.data');
            Route::get('/members/export', [WeeklyAttendanceReportController::class, 'memberExport'])->name('weekly-attendance.reports.members.export');

            Route::get('/audit-logs', [WeeklyAttendanceReportController::class, 'auditLogsIndex'])->name('weekly-attendance.reports.audit-logs');
            Route::get('/audit-logs/data', [WeeklyAttendanceReportController::class, 'auditLogsData'])->name('weekly-attendance.reports.audit-logs.data');
        });
    });

    Route::prefix('parish-staff')->group(function () {
        Route::get('/', [ParishStaffController::class, 'index'])->name('parish-staff.index');
        Route::post('/', [ParishStaffController::class, 'store'])->name('parish-staff.store');
        Route::patch('/{staff}', [ParishStaffController::class, 'update'])->name('parish-staff.update');
        Route::delete('/{staff}', [ParishStaffController::class, 'destroy'])->name('parish-staff.destroy');

        Route::post('/{staff}/register-as-member', [ParishStaffController::class, 'registerAsMember'])->name('parish-staff.register-as-member');
        Route::post('/{staff}/transfer-member', [ParishStaffController::class, 'transferMember'])->name('parish-staff.transfer-member');

        Route::get('/positions', [ParishStaffPositionController::class, 'index'])->name('parish-staff-positions.index');
        Route::post('/positions', [ParishStaffPositionController::class, 'store'])->name('parish-staff-positions.store');
        Route::patch('/positions/{role}', [ParishStaffPositionController::class, 'update'])->name('parish-staff-positions.update');
        Route::delete('/positions/{role}', [ParishStaffPositionController::class, 'destroy'])->name('parish-staff-positions.destroy');

        Route::post('/{staff}/assignments', [ParishStaffController::class, 'storeAssignment'])->name('parish-staff.assignments.store');
        Route::patch('/{staff}/assignments/{assignment}', [ParishStaffController::class, 'updateAssignment'])->name('parish-staff.assignments.update');

        Route::post('/{staff}/login', [ParishStaffController::class, 'createLogin'])->name('parish-staff.login.create');
        Route::delete('/{staff}/login', [ParishStaffController::class, 'disableLogin'])->name('parish-staff.login.disable');
    });
});

require __DIR__.'/auth.php';
