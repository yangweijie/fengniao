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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['browser', 'api'])->default('browser');
            $table->enum('status', ['enabled', 'disabled'])->default('enabled');
            $table->string('cron_expression');
            $table->longText('script_content')->nullable();
            $table->json('workflow_data')->nullable();
            $table->string('domain')->nullable();
            $table->boolean('is_exclusive')->default(false);
            $table->json('login_config')->nullable();
            $table->json('env_vars')->nullable();
            $table->json('notification_config')->nullable();
            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index('domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
