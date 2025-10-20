<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('completed_tasks', function (Blueprint $table) {
            // Drop old constraints first (PostgreSQL requires this)
            $table->dropForeign(['task_id']);
            $table->dropForeign(['advert_id']);

            // Modify columns to be nullable
            $table->unsignedBigInteger('task_id')->nullable()->change();
            $table->unsignedBigInteger('advert_id')->nullable()->change();

            // Recreate foreign keys
            $table->foreign('task_id')
                ->references('id')
                ->on('tasks')
                ->onDelete('cascade');

            $table->foreign('advert_id')
                ->references('id')
                ->on('advertises')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('completed_tasks', function (Blueprint $table) {
            // rollback to non-nullable (if needed)
            $table->dropForeign(['task_id']);
            $table->dropForeign(['advert_id']);

            $table->unsignedBigInteger('task_id')->nullable(false)->change();
            $table->unsignedBigInteger('advert_id')->nullable(false)->change();
        });
    }
};
