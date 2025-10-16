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
        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128)->unique();
            $table->string('description', 255)->nullable();
            
            // Queue Strategy
            $table->string('strategy', 20)->default('ringall'); // ringall, roundrobin, leastrecent, fewestcalls, random, rrmemory
            
            // Timing settings
            $table->integer('timeout')->default(15); // Ring timeout per member
            $table->integer('retry')->default(5); // Seconds to wait before retrying
            $table->integer('wrapuptime')->default(0); // Wrap-up time after call
            $table->integer('maxlen')->default(0); // Max queue length (0 = unlimited)
            
            // Announce settings
            $table->boolean('announce_holdtime')->default(true);
            $table->boolean('announce_position')->default(true);
            $table->integer('announce_frequency')->default(90);
            $table->string('periodic_announce', 255)->nullable();
            $table->integer('periodic_announce_frequency')->default(0);
            
            // Music on hold
            $table->string('musicclass', 128)->default('default');
            
            // Service Level Agreement
            $table->integer('servicelevel')->default(60); // Service level in seconds
            
            // Member settings
            $table->boolean('autofill')->default(true);
            $table->boolean('autopause')->default(false);
            $table->integer('autopausedelay')->default(0);
            $table->string('setinterfacevar', 20)->default('no');
            
            // Reporting
            $table->boolean('eventwhencalled')->default(true);
            $table->boolean('eventmemberstatus')->default(true);
            $table->boolean('reportholdtime')->default(true);
            $table->string('memberdelay', 10)->default('0');
            $table->string('weight', 10)->default('0');
            
            // Join/Leave settings
            $table->string('joinempty', 50)->default('yes');
            $table->string('leavewhenempty', 50)->default('no');
            
            // Monitoring
            $table->string('monitor_type', 20)->nullable(); // MixMonitor, Monitor
            $table->string('monitor_format', 20)->default('wav');
            
            // Status
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('is_active');
            $table->index('strategy');
        });

        // Queue Members (Agents)
        Schema::create('queue_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_id')->constrained('queues')->onDelete('cascade');
            $table->foreignId('extension_id')->constrained('extensions')->onDelete('cascade');
            
            // Member settings
            $table->string('interface', 128); // SIP/100, PJSIP/101, etc.
            $table->string('membername', 128)->nullable();
            $table->integer('penalty')->default(0); // Priority (lower = higher priority)
            $table->boolean('paused')->default(false);
            $table->string('paused_reason', 255)->nullable();
            
            // Statistics
            $table->integer('calls_taken')->default(0);
            $table->timestamp('last_call')->nullable();
            $table->integer('last_pause')->default(0);
            $table->string('state', 20)->default('available'); // available, busy, unavailable
            
            $table->timestamps();

            // Indexes
            $table->unique(['queue_id', 'extension_id']);
            $table->index('paused');
            $table->index('state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_members');
        Schema::dropIfExists('queues');
    }
};

