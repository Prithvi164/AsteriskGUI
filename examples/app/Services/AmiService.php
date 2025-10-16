<?php

namespace App\Services;

use PAMI\Client\Impl\ClientImpl;
use PAMI\Message\Action\LoginAction;
use PAMI\Message\Action\LogoffAction;
use PAMI\Message\Action\CommandAction;
use PAMI\Message\Action\OriginateAction;
use PAMI\Message\Action\HangupAction;
use PAMI\Message\Action\QueueStatusAction;
use PAMI\Message\Action\CoreShowChannelsAction;
use PAMI\Listener\IEventListener;
use App\Exceptions\AmiConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Service for interacting with Asterisk Manager Interface (AMI)
 * 
 * This service handles all communication with Asterisk via AMI,
 * including connection management, sending commands, and receiving events.
 */
class AmiService
{
    protected ?ClientImpl $client = null;
    protected bool $connected = false;
    protected array $config;

    public function __construct()
    {
        $this->config = [
            'host' => config('asterisk.ami.host'),
            'port' => config('asterisk.ami.port'),
            'username' => config('asterisk.ami.username'),
            'password' => config('asterisk.ami.password'),
            'connect_timeout' => config('asterisk.ami.connect_timeout'),
            'read_timeout' => config('asterisk.ami.read_timeout'),
            'scheme' => config('asterisk.ami.scheme'),
        ];
    }

    /**
     * Connect to Asterisk Manager Interface
     * 
     * @return bool
     * @throws AmiConnectionException
     */
    public function connect(): bool
    {
        if ($this->connected) {
            return true;
        }

        try {
            $this->client = new ClientImpl($this->config);
            $this->client->open();
            $this->connected = true;

            Log::info('AMI Connection established', [
                'host' => $this->config['host'],
                'port' => $this->config['port'],
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('AMI Connection failed', [
                'error' => $e->getMessage(),
                'host' => $this->config['host'],
            ]);

            throw new AmiConnectionException(
                'Failed to connect to Asterisk: ' . $e->getMessage()
            );
        }
    }

    /**
     * Disconnect from AMI
     * 
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->client && $this->connected) {
            try {
                $this->client->close();
                $this->connected = false;
                Log::info('AMI Connection closed');
            } catch (\Exception $e) {
                Log::error('Error closing AMI connection', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Get all active channels (calls)
     * 
     * @return array
     */
    public function getActiveChannels(): array
    {
        $this->ensureConnected();

        try {
            $action = new CoreShowChannelsAction();
            $response = $this->client->send($action);

            if (!$response->isSuccess()) {
                Log::warning('Failed to get active channels', [
                    'response' => $response->getMessage()
                ]);
                return [];
            }

            $events = $response->getEvents();
            $channels = [];

            foreach ($events as $event) {
                if ($event->getName() === 'CoreShowChannel') {
                    $channels[] = [
                        'channel' => $event->getKey('Channel'),
                        'channel_state' => $event->getKey('ChannelState'),
                        'channel_state_desc' => $event->getKey('ChannelStateDesc'),
                        'caller_id_num' => $event->getKey('CallerIDNum'),
                        'caller_id_name' => $event->getKey('CallerIDName'),
                        'connected_line_num' => $event->getKey('ConnectedLineNum'),
                        'context' => $event->getKey('Context'),
                        'extension' => $event->getKey('Exten'),
                        'priority' => $event->getKey('Priority'),
                        'application' => $event->getKey('Application'),
                        'application_data' => $event->getKey('ApplicationData'),
                        'duration' => $event->getKey('Duration'),
                    ];
                }
            }

            return $channels;
        } catch (\Exception $e) {
            Log::error('Error getting active channels', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Originate a new call (Click-to-Call)
     * 
     * @param string $extension The extension to call from
     * @param string $destination The number to call
     * @param string $context The dialplan context
     * @param int $timeout Call timeout in seconds
     * @return bool
     */
    public function originateCall(
        string $extension,
        string $destination,
        string $context = 'from-internal',
        int $timeout = 30
    ): bool {
        $this->ensureConnected();

        try {
            $action = new OriginateAction("SIP/$extension");
            $action->setExtension($destination);
            $action->setContext($context);
            $action->setPriority(1);
            $action->setTimeout($timeout * 1000);
            $action->setCallerId($extension);

            $response = $this->client->send($action);

            if ($response->isSuccess()) {
                Log::info('Call originated successfully', [
                    'extension' => $extension,
                    'destination' => $destination,
                ]);
                return true;
            } else {
                Log::warning('Call originate failed', [
                    'extension' => $extension,
                    'destination' => $destination,
                    'response' => $response->getMessage(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Error originating call', [
                'error' => $e->getMessage(),
                'extension' => $extension,
                'destination' => $destination,
            ]);
            return false;
        }
    }

    /**
     * Hangup a channel
     * 
     * @param string $channel The channel name
     * @return bool
     */
    public function hangupChannel(string $channel): bool
    {
        $this->ensureConnected();

        try {
            $action = new HangupAction($channel);
            $response = $this->client->send($action);

            if ($response->isSuccess()) {
                Log::info('Channel hung up', ['channel' => $channel]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Error hanging up channel', [
                'error' => $e->getMessage(),
                'channel' => $channel,
            ]);
            return false;
        }
    }

    /**
     * Get queue status and statistics
     * 
     * @param string|null $queueName Specific queue or all if null
     * @return array
     */
    public function getQueueStatus(?string $queueName = null): array
    {
        $this->ensureConnected();

        try {
            $action = new QueueStatusAction($queueName);
            $response = $this->client->send($action);

            if (!$response->isSuccess()) {
                return [];
            }

            $events = $response->getEvents();
            $queues = [];
            $currentQueue = null;

            foreach ($events as $event) {
                $eventName = $event->getName();

                if ($eventName === 'QueueParams') {
                    $currentQueue = $event->getKey('Queue');
                    $queues[$currentQueue] = [
                        'name' => $currentQueue,
                        'max' => $event->getKey('Max'),
                        'strategy' => $event->getKey('Strategy'),
                        'calls' => $event->getKey('Calls'),
                        'holdtime' => $event->getKey('Holdtime'),
                        'talktime' => $event->getKey('TalkTime'),
                        'completed' => $event->getKey('Completed'),
                        'abandoned' => $event->getKey('Abandoned'),
                        'service_level' => $event->getKey('ServiceLevel'),
                        'service_level_perf' => $event->getKey('ServicelevelPerf'),
                        'members' => [],
                        'entries' => [],
                    ];
                } elseif ($eventName === 'QueueMember' && $currentQueue) {
                    $queues[$currentQueue]['members'][] = [
                        'name' => $event->getKey('Name'),
                        'location' => $event->getKey('Location'),
                        'status' => $event->getKey('Status'),
                        'paused' => $event->getKey('Paused'),
                        'calls_taken' => $event->getKey('CallsTaken'),
                        'last_call' => $event->getKey('LastCall'),
                        'penalty' => $event->getKey('Penalty'),
                    ];
                } elseif ($eventName === 'QueueEntry' && $currentQueue) {
                    $queues[$currentQueue]['entries'][] = [
                        'position' => $event->getKey('Position'),
                        'channel' => $event->getKey('Channel'),
                        'caller_id_num' => $event->getKey('CallerIDNum'),
                        'caller_id_name' => $event->getKey('CallerIDName'),
                        'wait_time' => $event->getKey('Wait'),
                    ];
                }
            }

            return array_values($queues);
        } catch (\Exception $e) {
            Log::error('Error getting queue status', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Execute a CLI command
     * 
     * @param string $command
     * @return string|null
     */
    public function executeCommand(string $command): ?string
    {
        $this->ensureConnected();

        try {
            $action = new CommandAction($command);
            $response = $this->client->send($action);

            if ($response->isSuccess()) {
                return implode("\n", $response->getKeys());
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error executing command', [
                'error' => $e->getMessage(),
                'command' => $command,
            ]);
            return null;
        }
    }

    /**
     * Get SIP peer status
     * 
     * @return array
     */
    public function getSipPeers(): array
    {
        $output = $this->executeCommand('sip show peers');
        
        if (!$output) {
            return [];
        }

        $lines = explode("\n", $output);
        $peers = [];

        foreach ($lines as $line) {
            // Parse SIP peer information from output
            // Format: Name/username  Host            Dyn Forcerport ACL Port     Status
            if (preg_match('/^(\S+)\s+(\S+)\s+(\S+)\s+/', $line, $matches)) {
                $peers[] = [
                    'name' => $matches[1],
                    'host' => $matches[2],
                    'status' => strpos($line, 'OK') !== false ? 'online' : 'offline',
                ];
            }
        }

        return $peers;
    }

    /**
     * Register an event listener
     * 
     * @param IEventListener $listener
     * @return void
     */
    public function addEventListener(IEventListener $listener): void
    {
        $this->ensureConnected();
        $this->client->registerEventListener($listener);
    }

    /**
     * Process events (for event listener daemon)
     * 
     * @return void
     */
    public function processEvents(): void
    {
        $this->ensureConnected();
        $this->client->process();
    }

    /**
     * Ensure connection is active, connect if not
     * 
     * @return void
     * @throws AmiConnectionException
     */
    protected function ensureConnected(): void
    {
        if (!$this->connected) {
            $this->connect();
        }
    }

    /**
     * Get system status
     * 
     * @return array
     */
    public function getSystemStatus(): array
    {
        $cacheKey = 'asterisk_system_status';
        
        return Cache::remember($cacheKey, 30, function () {
            $uptime = $this->executeCommand('core show uptime');
            $channels = $this->executeCommand('core show channels count');
            
            return [
                'uptime' => $this->parseUptime($uptime),
                'active_channels' => $this->parseChannelCount($channels),
                'connected' => $this->connected,
            ];
        });
    }

    /**
     * Parse uptime from CLI output
     * 
     * @param string|null $output
     * @return string
     */
    protected function parseUptime(?string $output): string
    {
        if (!$output) {
            return 'Unknown';
        }

        if (preg_match('/System uptime:\s*(.+)/', $output, $matches)) {
            return trim($matches[1]);
        }

        return 'Unknown';
    }

    /**
     * Parse channel count from CLI output
     * 
     * @param string|null $output
     * @return int
     */
    protected function parseChannelCount(?string $output): int
    {
        if (!$output) {
            return 0;
        }

        if (preg_match('/(\d+)\s+active\s+channel/', $output, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    /**
     * Destructor - ensure connection is closed
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}

