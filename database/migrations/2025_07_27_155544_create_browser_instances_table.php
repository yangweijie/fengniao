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
        Schema::create('browser_instances', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->enum('status', ['idle', 'busy', 'error'])->default('idle');
            $table->string('primary_domain')->nullable();
            $table->boolean('is_exclusive')->default(false);
            $table->json('active_tabs')->nullable();
            $table->json('resource_usage')->nullable();
            $table->timestamp('last_activity_at')->useCurrent();
            $table->timestamps();

            $table->index(['status', 'is_exclusive']);
            $table->index('primary_domain');
            $table->index('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('browser_instances');
    }
};
