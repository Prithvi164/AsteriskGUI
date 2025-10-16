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
        Schema::create('active_calls', function (Blueprint $table) {
            $table->id();
            $table->string('channel')->unique();
            $table->string('unique_id')->index();
            $table->string('caller_id_num', 50)->nullable();
            $table->string('caller_id_name', 100)->nullable();
            $table->string('connected_line_num', 50)->nullable();
            $table->string('connected_line_name', 100)->nullable();
            $table->string('destination', 50)->nullable();
            $table->foreignId('extension_id')->nullable()->constrained('extensions')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('context', 80)->nullable();
            $table->string('status', 20)->default('ringing'); // ringing, up, busy, down
            $table->string('application', 100)->nullable();
            $table->text('application_data')->nullable();
            $table->string('bridge_id', 50)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('status');
            $table->index('started_at');
            $table->index(['extension_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_calls');
    }
};

