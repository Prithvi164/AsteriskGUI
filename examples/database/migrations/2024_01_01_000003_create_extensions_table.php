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
        Schema::create('extensions', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->string('name', 100);
            $table->string('email', 100)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // SIP Settings
            $table->string('technology', 10)->default('SIP'); // SIP, IAX2, PJSIP
            $table->string('secret', 100)->nullable();
            $table->string('context', 80)->default('from-internal');
            $table->string('host', 50)->default('dynamic');
            $table->string('type', 20)->default('friend'); // friend, peer, user
            $table->string('nat', 20)->default('yes');
            $table->string('qualify', 20)->default('yes');
            $table->string('canreinvite', 20)->default('no');
            
            // Codecs
            $table->text('allow')->nullable(); // Allowed codecs (comma-separated)
            $table->text('disallow')->default('all');
            
            // Call Settings
            $table->integer('call_limit')->default(1);
            $table->boolean('call_waiting')->default(true);
            $table->string('call_forward')->nullable();
            $table->boolean('dnd')->default(false); // Do Not Disturb
            
            // Voicemail
            $table->boolean('voicemail_enabled')->default(true);
            $table->string('voicemail_pin', 20)->nullable();
            $table->string('voicemail_email')->nullable();
            
            // Recording
            $table->boolean('record_calls')->default(false);
            $table->string('recording_format', 10)->default('wav');
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_registered')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('is_active');
            $table->index('technology');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extensions');
    }
};

