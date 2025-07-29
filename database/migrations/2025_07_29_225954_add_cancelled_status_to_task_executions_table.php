<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 对于SQLite，我们需要重建表来修改ENUM约束
        // 因为SQLite不支持直接修改CHECK约束

        // 禁用外键约束
        DB::statement('PRAGMA foreign_keys=OFF');

        // 清理可能存在的临时表
        Schema::dropIfExists('task_executions_temp');

        // 1. 创建新的临时表，包含cancelled状态
        Schema::create('task_executions_temp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['running', 'success', 'failed', 'cancelled'])->default('running');
            $table->timestamp('start_time')->useCurrent();
            $table->timestamp('end_time')->nullable();
            $table->integer('duration')->nullable(); // in seconds
            $table->string('browser_instance_id')->nullable();
            $table->string('tab_id')->nullable();
            $table->text('error_message')->nullable();
            $table->json('screenshots')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'status']);
            $table->index('start_time');
        });

        // 2. 复制数据
        DB::statement("
            INSERT INTO task_executions_temp (id, task_id, status, start_time, end_time, duration, browser_instance_id, tab_id, error_message, screenshots, created_at, updated_at)
            SELECT id, task_id, status, start_time, end_time, duration, browser_instance_id, tab_id, error_message, screenshots, created_at, updated_at
            FROM task_executions
        ");

        // 3. 删除原表
        Schema::dropIfExists('task_executions');

        // 4. 重命名临时表
        Schema::rename('task_executions_temp', 'task_executions');

        // 重新启用外键约束
        DB::statement('PRAGMA foreign_keys=ON');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 回滚：移除cancelled状态

        // 禁用外键约束
        DB::statement('PRAGMA foreign_keys=OFF');

        // 清理可能存在的临时表
        Schema::dropIfExists('task_executions_temp');

        // 1. 创建临时表（使用原始状态枚举）
        Schema::create('task_executions_temp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['running', 'success', 'failed'])->default('running');
            $table->timestamp('start_time')->useCurrent();
            $table->timestamp('end_time')->nullable();
            $table->integer('duration')->nullable(); // in seconds
            $table->string('browser_instance_id')->nullable();
            $table->string('tab_id')->nullable();
            $table->text('error_message')->nullable();
            $table->json('screenshots')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'status']);
            $table->index('start_time');
        });

        // 2. 复制数据，将cancelled状态转换为failed
        DB::statement("
            INSERT INTO task_executions_temp (id, task_id, status, start_time, end_time, duration, browser_instance_id, tab_id, error_message, screenshots, created_at, updated_at)
            SELECT
                id,
                task_id,
                CASE WHEN status = 'cancelled' THEN 'failed' ELSE status END as status,
                start_time,
                end_time,
                duration,
                browser_instance_id,
                tab_id,
                error_message,
                screenshots,
                created_at,
                updated_at
            FROM task_executions
        ");

        // 3. 删除原表
        Schema::dropIfExists('task_executions');

        // 4. 重命名临时表
        Schema::rename('task_executions_temp', 'task_executions');

        // 重新启用外键约束
        DB::statement('PRAGMA foreign_keys=ON');
    }
};
