<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class extends Migration
{
    public function up(): void
    {
        $duplicate = DB::table('initialize_deposits')
            ->select('reference')
            ->whereNotNull('reference')
            ->groupBy('reference')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate) {
            throw new RuntimeException(
                'Duplicate payment references exist in initialize_deposits. Reconcile them before applying the unique index.'
            );
        }

        Schema::table('initialize_deposits', function (Blueprint $table) {
            $table->unique('reference', 'initialize_deposits_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::table('initialize_deposits', function (Blueprint $table) {
            $table->dropUnique('initialize_deposits_reference_unique');
        });
    }
};
