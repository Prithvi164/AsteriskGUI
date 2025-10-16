<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This table stores Call Detail Records (CDR)
     * Compatible with Asterisk's CDR format
     */
    public function up(): void
    {
        Schema::create('cdrs', function (Blueprint $table) {
            $table->id();
            
            // Call identification
            $table->string('accountcode', 20)->nullable();
            $table->string('src', 80)->nullable(); // Source (caller)
            $table->string('dst', 80)->nullable(); // Destination (callee)
            $table->string('dcontext', 80)->nullable();
            $table->string('clid', 80)->nullable(); // Caller ID
            $table->string('channel', 80)->nullable();
            $table->string('dstchannel', 80)->nullable();
            $table->string('lastapp', 80)->nullable();
            $table->string('lastdata', 80)->nullable();
            
            // Call timing
            $table->timestamp('calldate')->nullable(); // Call start date/time
            $table->timestamp('answerdate')->nullable(); // Answer date/time
            $table->timestamp('enddate')->nullable(); // End date/time
            $table->integer('duration')->default(0); // Total duration in seconds
            $table->integer('billsec')->default(0); // Billable seconds (after answer)
            
            // Call disposition
            $table->string('disposition', 45)->nullable(); // ANSWERED, NO ANSWER, BUSY, FAILED
            $table->string('amaflags', 45)->nullable();
            
            // Additional info
            $table->string('uniqueid', 32)->index();
            $table->string('userfield', 255)->nullable();
            $table->string('recordingfile', 255)->nullable(); // Path to recording
            
            // Custom fields for GUI
            $table->foreignId('extension_id')->nullable()->constrained('extensions')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('call_type', 20)->nullable(); // inbound, outbound, internal
            $table->decimal('call_cost', 10, 4)->default(0.0000);
            $table->text('notes')->nullable();
            
            $table->timestamps();

            // Indexes for performance
            $table->index('calldate');
            $table->index('src');
            $table->index('dst');
            $table->index('disposition');
            $table->index(['extension_id', 'calldate']);
            $table->index(['user_id', 'calldate']);
            $table->index('call_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cdrs');
    }
};

