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
        Schema::create('cookies', function (Blueprint $table) {
            $table->id();
            $table->string('domain');
            $table->string('account')->nullable();
            $table->json('cookie_data');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->useCurrent();
            $table->boolean('is_valid')->default(true);
            $table->timestamps();

            $table->unique(['domain', 'account']);
            $table->index(['domain', 'is_valid']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cookies');
    }
};
