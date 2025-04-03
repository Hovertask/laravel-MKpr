<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add new temporary JSON column
        Schema::table('tasks', function (Blueprint $table) {
            $table->json('platforms_temp')->nullable()->after('platforms');
        });

        // 2. Migrate data in a database-specific way
        if (config('database.default') === 'pgsql') {
            // PostgreSQL approach
            DB::statement('UPDATE tasks SET platforms_temp = 
                CASE WHEN platforms IS NULL THEN NULL 
                ELSE json_build_array(platforms) END');
        } else {
            // MySQL approach
            DB::statement('UPDATE tasks SET platforms_temp = 
                CASE WHEN platforms IS NULL THEN NULL 
                ELSE JSON_ARRAY(platforms) END');
        }

        // 3. Drop original column
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('platforms');
        });

        // 4. Rename temporary column to original name
        Schema::table('tasks', function (Blueprint $table) {
            $table->renameColumn('platforms_temp', 'platforms');
        });
    }

    public function down(): void
    {
        // 1. Add back the original string column
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('platforms_old')->nullable()->after('platforms');
        });

        // 2. Migrate data back
        if (config('database.default') === 'pgsql') {
            // PostgreSQL approach
            DB::statement('UPDATE tasks SET platforms_old = 
                CASE WHEN platforms IS NULL THEN NULL 
                ELSE platforms->>0 END');
        } else {
            // MySQL approach
            DB::statement('UPDATE tasks SET platforms_old = 
                CASE WHEN platforms IS NULL THEN NULL 
                ELSE JSON_UNQUOTE(JSON_EXTRACT(platforms, "$[0]")) END');
        }

        // 3. Drop JSON column
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('platforms');
        });

        // 4. Rename back to original
        Schema::table('tasks', function (Blueprint $table) {
            $table->renameColumn('platforms_old', 'platforms');
        });
    }
};