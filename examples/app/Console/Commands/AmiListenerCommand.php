<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AmiService;
use App\Models\ActiveCall;
use App\Events\NewCallEvent;
use App\Events\CallHangupEvent;
use PAMI\Message\Event\EventMessage;
use PAMI\Listener\IEventListener;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * AMI Event Listener Command
 * 
 * This command runs as a daemon, listening to Asterisk Manager Interface events
 * and updating the database in real-time.
 * 
 * Run: php artisan ami:listen
 */
class AmiListenerCommand extends Command implements IEventListener
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ami:listen
                            {--reconnect-delay=5 : Seconds to wait before reconnecting}
                            {--max-reconnects=0 : Maximum reconnection attempts (0 = infinite)}';

    /**
     * The console command description.
     */
    protected $description = 'Listen to Asterisk Manager Interface events';

    protected AmiService $amiService;
    protected int $reconnectAttempts = 0;
    protected bool $shouldStop = false;

    /**
     * Execute the console command.
     */
    public function handle(AmiService $amiService): int
    {
        $this->amiService = $amiService;
        $maxReconnects = (int) $this->option('max-reconnects');
        $reconnectDelay = (int) $this->option('reconnect-delay');

        $this->info('Starting AMI Event Listener...');

        // Handle graceful shutdown
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, [$this, 'shutdown']);
        pcntl_signal(SIGINT, [$this, 'shutdown']);

        while (!$this->shouldStop) {
            try {
                $this->info('Connecting to Asterisk Manager Interface...');
                
                $this->amiService->connect();
                $this->amiService->addEventListener($this);
                
                $this->info('Connected! Listening for events...');
                $this->reconnectAttempts = 0;

                // Main event loop
                while (!$this->shouldStop) {
                    $this->amiService->processEvents();
                    usleep(10000); // 10ms delay to prevent CPU overload
                }

            } catch (\Exception $e) {
                $this->error('AMI Connection error: ' . $e->getMessage());
                
                Log::error('AMI Listener error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $this->reconnectAttempts++;

                if ($maxReconnects > 0 && $this->reconnectAttempts >= $maxReconnects) {
                    $this->error("Max reconnection attempts ($maxReconnects) reached. Exiting.");
                    return 1;
                }

                $this->warn("Reconnecting in {$reconnectDelay} seconds... (Attempt {$this->reconnectAttempts})");
                sleep($reconnectDelay);
            }
        }

        $this->info('AMI Listener stopped gracefully.');
        return 0;
    }

    /**
     * Handle AMI events
     * 
     * @param EventMessage $event
     */
    public function handle(EventMessage $event): void
    {
        $eventName = $event->getName();

        try {
            switch ($eventName) {
                case 'Newchannel':
                    $this->handleNewChannel($event);
                    break;

                case 'Newstate':
                    $this->handleNewState($event);
                    break;

                case 'Hangup':
                    $this->handleHangup($event);
                    break;

                case 'QueueMemberStatus':
                    $this->handleQueueMemberStatus($event);
                    break;

                case 'QueueCallerJoin':
                    $this->handleQueueCallerJoin($event);
                    break;

                case 'QueueCallerLeave':
                    $this->handleQueueCallerLeave($event);
                    break;

                default:
                    // Log unhandled events if needed
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Error handling AMI event', [
                'event' => $eventName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle new channel event (new call)
     */
    protected function handleNewChannel(EventMessage $event): void
    {
        $channel = $event->getKey('Channel');
        $uniqueId = $event->getKey('Uniqueid');
        $callerIdNum = $event->getKey('CallerIDNum');
        $callerIdName = $event->getKey('CallerIDName');
        $context = $event->getKey('Context');
        $exten = $event->getKey('Exten');

        $this->line("New Channel: $channel ($callerIdNum)");

        // Create active call record
        $activeCall = ActiveCall::updateOrCreate(
            ['channel' => $channel],
            [
                'unique_id' => $uniqueId,
                'caller_id_num' => $callerIdNum,
                'caller_id_name' => $callerIdName,
                'destination' => $exten,
                'context' => $context,
                'status' => 'ringing',
                'started_at' => Carbon::now(),
            ]
        );

        // Broadcast event to WebSocket
        event(new NewCallEvent($activeCall));

        Log::info('New channel created', [
            'channel' => $channel,
            'caller_id' => $callerIdNum,
        ]);
    }

    /**
     * Handle channel state change
     */
    protected function handleNewState(EventMessage $event): void
    {
        $channel = $event->getKey('Channel');
        $channelState = $event->getKey('ChannelState');
        $channelStateDesc = $event->getKey('ChannelStateDesc');

        $activeCall = ActiveCall::where('channel', $channel)->first();

        if ($activeCall) {
            // Map channel states to our status
            $status = $this->mapChannelState($channelState, $channelStateDesc);
            
            $updateData = ['status' => $status];

            // If call is answered, record the time
            if ($status === 'up' && !$activeCall->answered_at) {
                $updateData['answered_at'] = Carbon::now();
            }

            $activeCall->update($updateData);

            $this->line("Channel State Changed: $channel -> $status");
        }
    }

    /**
     * Handle hangup event
     */
    protected function handleHangup(EventMessage $event): void
    {
        $channel = $event->getKey('Channel');
        $uniqueId = $event->getKey('Uniqueid');
        $cause = $event->getKey('Cause');
        $causeTxt = $event->getKey('Cause-txt');

        $this->line("Hangup: $channel (Cause: $causeTxt)");

        $activeCall = ActiveCall::where('channel', $channel)->first();

        if ($activeCall) {
            // Calculate final duration
            $duration = $activeCall->duration;

            // Broadcast hangup event
            event(new CallHangupEvent($activeCall, $cause, $causeTxt, $duration));

            // Remove from active calls
            $activeCall->delete();

            Log::info('Call ended', [
                'channel' => $channel,
                'duration' => $duration,
                'cause' => $causeTxt,
            ]);
        }
    }

    /**
     * Handle queue member status change
     */
    protected function handleQueueMemberStatus(EventMessage $event): void
    {
        $queue = $event->getKey('Queue');
        $memberName = $event->getKey('MemberName');
        $status = $event->getKey('Status');
        $paused = $event->getKey('Paused');

        $this->line("Queue Member Status: $queue - $memberName -> Status: $status, Paused: $paused");

        // Update queue member status in database
        // Implementation depends on your queue member model
    }

    /**
     * Handle caller joining queue
     */
    protected function handleQueueCallerJoin(EventMessage $event): void
    {
        $queue = $event->getKey('Queue');
        $callerIdNum = $event->getKey('CallerIDNum');
        $position = $event->getKey('Position');

        $this->line("Caller Joined Queue: $queue - $callerIdNum (Position: $position)");
    }

    /**
     * Handle caller leaving queue
     */
    protected function handleQueueCallerLeave(EventMessage $event): void
    {
        $queue = $event->getKey('Queue');
        $callerIdNum = $event->getKey('CallerIDNum');

        $this->line("Caller Left Queue: $queue - $callerIdNum");
    }

    /**
     * Map Asterisk channel state to our internal status
     */
    protected function mapChannelState(string $channelState, string $channelStateDesc): string
    {
        return match ($channelState) {
            '4' => 'ringing',  // Ring
            '6' => 'up',       // Up
            '5' => 'ringing',  // Ringing
            '0' => 'down',     // Down
            default => strtolower($channelStateDesc),
        };
    }

    /**
     * Handle graceful shutdown
     */
    public function shutdown(): void
    {
        $this->warn("\nReceived shutdown signal. Stopping gracefully...");
        $this->shouldStop = true;
        $this->amiService->disconnect();
    }
}

