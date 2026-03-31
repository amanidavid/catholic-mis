<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petty_cash_voucher_attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique('petty_cash_voucher_attachments_uuid_unique');
            $table->foreignId('petty_cash_voucher_id')->constrained('petty_cash_vouchers', 'id')->onDelete('cascade');
            $table->string('original_name', 255);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('storage_disk', 50)->default('local');
            $table->string('storage_path', 500);
            $table->string('sha256', 64)->nullable();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users', 'id')->onDelete('set null');
            $table->timestamps();

            $table->index(['petty_cash_voucher_id', 'id'], 'idx_pcv_attachments_voucher_id');
            $table->index('sha256', 'idx_pcv_attachments_sha256');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_cash_voucher_attachments');
    }
};
