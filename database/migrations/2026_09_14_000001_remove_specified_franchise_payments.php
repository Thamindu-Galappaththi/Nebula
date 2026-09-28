<?php

use App\Support\SpecifiedFranchisePaymentRemoval;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SpecifiedFranchisePaymentRemoval::run();
    }

    public function down(): void
    {
        // Original franchise amounts cannot be restored safely.
    }
};
