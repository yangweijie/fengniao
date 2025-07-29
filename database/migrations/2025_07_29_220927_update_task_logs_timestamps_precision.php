<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('task_logs', function (Blueprint $table) {
            // 更新时间戳字段为包含毫秒的精度
            $table->timestamp('created_at', 3)->change();
            $table->timestamp('updated_at', 3)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_logs', function (Blueprint $table) {
            // 恢复为默认精度
            $table->timestamp('created_at')->change();
            $table->timestamp('updated_at')->change();
        });
    }
};
