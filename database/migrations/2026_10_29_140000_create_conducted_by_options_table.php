<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('conducted_by_options')) {
            Schema::create('conducted_by_options', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        $now = now();
        $defaults = [
            'SLT-MOBITEL Nebula Institute of Technology',
            'Pearson',
            'University of Hertfordshire',
        ];

        foreach ($defaults as $name) {
            DB::table('conducted_by_options')->insertOrIgnore([
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('conducted_by_options');
    }
};
