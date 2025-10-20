<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('completed_tasks', function (Blueprint $table) {
            // ✅ Add new nullable advert_id column
            $table->unsignedBigInteger('advert_id')->nullable()->after('task_id');

            // ✅ Add foreign key constraint to adverts table
            $table->foreign('advert_id')
                ->references('id')
                ->on('advertises')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('completed_tasks', function (Blueprint $table) {
            // Drop constraint and column when rolling back
            $table->dropForeign(['advert_id']);
            $table->dropColumn('advert_id');
        });
    }
};
