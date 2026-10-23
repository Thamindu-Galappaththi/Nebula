<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'foundation_program')) {
                $table->boolean('foundation_program')->default(false)->after('institute_location');
            }
            if (!Schema::hasColumn('students', 'btec_completed')) {
                $table->boolean('btec_completed')->default(false)->after('foundation_program');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('students', 'foundation_program') ? 'foundation_program' : null,
                Schema::hasColumn('students', 'btec_completed') ? 'btec_completed' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
