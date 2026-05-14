<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pastoral_service_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('parish_id')->constrained('parishes', 'id')->onDelete('restrict');
            $table->string('name');
            $table->string('name_normalized');
            $table->string('code');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['parish_id', 'code'], 'uq_pastoral_service_categories_parish_code');
            $table->index(['parish_id', 'is_active', 'name_normalized'], 'idx_pastoral_service_categories_parish_active_name');
            $table->index(['parish_id', 'sort_order', 'name_normalized'], 'idx_pastoral_service_categories_parish_sort_name');
        });

        Schema::create('pastoral_service_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('parish_id')->constrained('parishes', 'id')->onDelete('restrict');
            $table->foreignId('jumuiya_id')->constrained('jumuiyas', 'id')->onDelete('restrict');
            $table->foreignId('requested_by_member_id')->nullable()->constrained('members', 'id')->onDelete('set null');
            $table->date('request_date');
            $table->date('preferred_service_date')->nullable();
            $table->date('scheduled_service_date')->nullable();
            $table->string('urgency')->default('normal');
            $table->text('notes')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('in_progress_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users', 'id')->onDelete('set null');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users', 'id')->onDelete('set null');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users', 'id')->onDelete('set null');
            $table->timestamps();

            $table->index(['parish_id', 'status', 'request_date', 'id'], 'idx_service_requests_parish_status_date_id');
            $table->index(['parish_id', 'jumuiya_id', 'status', 'request_date'], 'idx_service_requests_parish_jumuiya_status_date');
            $table->index(['jumuiya_id', 'request_date', 'id'], 'idx_service_requests_jumuiya_date_id');
            $table->index(['parish_id', 'scheduled_service_date', 'status'], 'idx_service_requests_parish_schedule_status');
        });

        Schema::create('pastoral_service_request_families', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pastoral_service_request_id')->constrained('pastoral_service_requests', 'id', 'fk_psrf_request')->onDelete('cascade');
            $table->foreignId('family_id')->constrained('families', 'id')->onDelete('restrict');
            $table->text('family_notes')->nullable();
            $table->timestamps();

            $table->unique(['pastoral_service_request_id', 'family_id'], 'uq_service_request_families_request_family');
            $table->index(['family_id', 'pastoral_service_request_id'], 'idx_service_request_families_family_request');
        });

        Schema::create('pastoral_service_request_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pastoral_service_request_family_id')->constrained('pastoral_service_request_families', 'id', 'fk_psri_family')->onDelete('cascade');
            $table->foreignId('pastoral_service_category_id')->constrained('pastoral_service_categories', 'id', 'fk_psri_category')->onDelete('restrict');
            $table->foreignId('target_member_id')->nullable()->constrained('members', 'id')->onDelete('set null');
            $table->text('description')->nullable();
            $table->date('requested_for_date')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['pastoral_service_category_id', 'status'], 'idx_service_request_items_category_status');
            $table->index(['requested_for_date', 'status'], 'idx_service_request_items_requested_for_status');
            $table->index(['target_member_id', 'status'], 'idx_service_request_items_target_status');
        });

        Schema::create('pastoral_service_request_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('parish_id')->constrained('parishes', 'id')->onDelete('restrict');
            $table->foreignId('pastoral_service_request_id')->constrained('pastoral_service_requests', 'id', 'fk_psre_request')->onDelete('cascade');
            $table->string('action', 60);
            $table->string('old_status', 60)->nullable();
            $table->string('new_status', 60)->nullable();
            $table->foreignId('performed_by_user_id')->nullable()->constrained('users', 'id')->onDelete('set null');
            $table->timestamp('performed_at');
            $table->text('notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['pastoral_service_request_id', 'performed_at'], 'idx_service_request_events_request_performed');
            $table->index(['parish_id', 'performed_at'], 'idx_service_request_events_parish_performed');
        });

        Schema::create('deceased_register_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('parish_id')->constrained('parishes', 'id')->onDelete('restrict');
            $table->foreignId('member_id')->constrained('members', 'id')->onDelete('restrict');
            $table->date('date_of_death');
            $table->time('time_of_death')->nullable();
            $table->string('place_of_death');
            $table->text('cause_of_death')->nullable();
            $table->string('death_certificate_number')->nullable();
            $table->string('hospital_or_health_facility')->nullable();
            $table->date('funeral_date')->nullable();
            $table->date('burial_date')->nullable();
            $table->string('burial_location_or_cemetery')->nullable();
            $table->string('funeral_mass_location')->nullable();
            $table->string('priest_or_celebrant_name')->nullable();
            $table->text('homily_or_remarks')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users', 'id')->onDelete('set null');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users', 'id')->onDelete('set null');
            $table->timestamps();

            $table->unique(['member_id'], 'uq_deceased_register_entries_member');
            $table->index(['parish_id', 'date_of_death', 'id'], 'idx_deceased_entries_parish_death_date_id');
            $table->index(['parish_id', 'burial_date', 'id'], 'idx_deceased_entries_parish_burial_date_id');
            $table->index(['parish_id', 'created_at', 'id'], 'idx_deceased_entries_parish_created_id');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->boolean('is_deceased')->default(false)->after('is_active');
            $table->date('date_of_death')->nullable()->after('is_deceased');

            $table->index(['jumuiya_id', 'is_deceased', 'last_name'], 'idx_members_jumuiya_deceased_last_name');
            $table->index(['is_deceased', 'date_of_death'], 'idx_members_deceased_date');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex('idx_members_jumuiya_deceased_last_name');
            $table->dropIndex('idx_members_deceased_date');
            $table->dropColumn(['is_deceased', 'date_of_death']);
        });

        Schema::dropIfExists('deceased_register_entries');
        Schema::dropIfExists('pastoral_service_request_events');
        Schema::dropIfExists('pastoral_service_request_items');
        Schema::dropIfExists('pastoral_service_request_families');
        Schema::dropIfExists('pastoral_service_requests');
        Schema::dropIfExists('pastoral_service_categories');
    }
};
