<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('attendance')) {
            return;
        }

        Schema::table('attendance', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance', 'session_start')) {
                $table->time('session_start')->nullable()->after('attendance_type');
            }
            if (!Schema::hasColumn('attendance', 'session_end')) {
                $table->time('session_end')->nullable()->after('session_start');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('attendance')) {
            return;
        }

        Schema::table('attendance', function (Blueprint $table) {
            if (Schema::hasColumn('attendance', 'session_end')) {
                $table->dropColumn('session_end');
            }
            if (Schema::hasColumn('attendance', 'session_start')) {
                $table->dropColumn('session_start');
            }
        });
    }
};
