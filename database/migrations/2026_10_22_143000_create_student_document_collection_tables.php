<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_document_requests', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash', 64)->nullable()->unique();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->string('notification_status', 32)->default('pending')->index();
            $table->string('document_deletion_status', 32)->default('retained')->index();
            $table->timestamp('link_expires_at')->nullable()->index();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamp('retention_expires_at')->nullable()->index();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('purged_at')->nullable();
            $table->string('submitted_full_name')->nullable();
            $table->string('submitted_id_value')->nullable();
            $table->string('submitted_email')->nullable();
            $table->string('submitted_phone', 30)->nullable();
            $table->text('submitted_notes')->nullable();
            $table->unsignedSmallInteger('submitted_file_count')->default(0);
            $table->timestamps();

            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('reviewed_by')->references('user_id')->on('users')->onDelete('set null');
            $table->index(['student_id', 'status']);
        });

        Schema::create('student_document_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_request_id');
            $table->string('document_type', 64)->index();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('checksum', 64)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('document_request_id')->references('id')->on('student_document_requests')->onDelete('cascade');
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('user_id')->on('users')->onDelete('set null');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_document_files');
        Schema::dropIfExists('student_document_requests');
        Schema::dropIfExists('notifications');
    }
};
