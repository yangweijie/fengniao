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
        // 对于SQLite，我们需要重建表来支持微秒精度
        // 因为SQLite不支持直接修改列类型

        // 禁用外键约束
        DB::statement('PRAGMA foreign_keys=OFF');

        // 清理可能存在的临时表
        Schema::dropIfExists('task_logs_temp');

        // 1. 创建临时表
        Schema::create('task_logs_temp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('execution_id')->constrained('task_executions')->onDelete('cascade');
            $table->enum('level', ['debug', 'info', 'warning', 'error'])->default('info');
            $table->text('message');
            $table->json('context')->nullable();
            $table->string('screenshot_path')->nullable();
            // 使用字符串类型存储微秒时间戳
            $table->string('created_at', 26); // YYYY-MM-DD HH:MM:SS.uuuuuu
            $table->string('updated_at', 26); // YYYY-MM-DD HH:MM:SS.uuuuuu

            $table->index(['execution_id', 'level']);
            $table->index('created_at');
        });

        // 2. 复制数据，将现有的datetime转换为微秒格式
        DB::statement("
            INSERT INTO task_logs_temp (id, execution_id, level, message, context, screenshot_path, created_at, updated_at)
            SELECT
                id,
                execution_id,
                level,
                message,
                context,
                screenshot_path,
                created_at || '.000000' as created_at,
                updated_at || '.000000' as updated_at
            FROM task_logs
        ");

        // 3. 删除原表
        Schema::dropIfExists('task_logs');

        // 4. 重命名临时表
        Schema::rename('task_logs_temp', 'task_logs');

        // 重新启用外键约束
        DB::statement('PRAGMA foreign_keys=ON');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 回滚：将字符串时间戳转换回datetime

        // 1. 创建临时表（使用原始结构）
        Schema::create('task_logs_temp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('execution_id')->constrained('task_executions')->onDelete('cascade');
            $table->enum('level', ['debug', 'info', 'warning', 'error'])->default('info');
            $table->text('message');
            $table->json('context')->nullable();
            $table->string('screenshot_path')->nullable();
            $table->timestamps();

            $table->index(['execution_id', 'level']);
            $table->index('created_at');
        });

        // 2. 复制数据，截断微秒部分
        DB::statement("
            INSERT INTO task_logs_temp (id, execution_id, level, message, context, screenshot_path, created_at, updated_at)
            SELECT
                id,
                execution_id,
                level,
                message,
                context,
                screenshot_path,
                substr(created_at, 1, 19) as created_at,
                substr(updated_at, 1, 19) as updated_at
            FROM task_logs
        ");

        // 3. 删除原表
        Schema::dropIfExists('task_logs');

        // 4. 重命名临时表
        Schema::rename('task_logs_temp', 'task_logs');
    }
};
