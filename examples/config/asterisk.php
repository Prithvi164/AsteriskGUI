<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Asterisk Manager Interface (AMI) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for connecting to Asterisk's Manager Interface.
    | This is used for real-time event monitoring and command execution.
    |
    */
    'ami' => [
        'host' => env('ASTERISK_AMI_HOST', '127.0.0.1'),
        'port' => env('ASTERISK_AMI_PORT', 5038),
        'username' => env('ASTERISK_AMI_USERNAME', 'admin'),
        'password' => env('ASTERISK_AMI_PASSWORD', 'password'),
        'connect_timeout' => env('ASTERISK_AMI_CONNECT_TIMEOUT', 10000),
        'read_timeout' => env('ASTERISK_AMI_READ_TIMEOUT', 10000),
        'scheme' => 'tcp://', // or 'ssl://' for secure connection
    ],

    /*
    |--------------------------------------------------------------------------
    | Asterisk REST Interface (ARI) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Asterisk's REST API (ARI).
    | Used for advanced call control and application development.
    |
    */
    'ari' => [
        'enabled' => env('ASTERISK_ARI_ENABLED', false),
        'host' => env('ASTERISK_ARI_HOST', '127.0.0.1'),
        'port' => env('ASTERISK_ARI_PORT', 8088),
        'username' => env('ASTERISK_ARI_USERNAME', 'ari_user'),
        'password' => env('ASTERISK_ARI_PASSWORD', 'ari_password'),
        'application' => env('ASTERISK_ARI_APPLICATION', 'asterisk-gui'),
        'endpoint' => env('ASTERISK_ARI_ENDPOINT', 'ari'),
    ],

    /*
    |--------------------------------------------------------------------------
    | CDR Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Asterisk's Call Detail Records database.
    | Can be the same or separate from the application database.
    |
    */
    'cdr' => [
        'connection' => env('ASTERISK_CDR_CONNECTION', 'mysql'),
        'table' => env('ASTERISK_CDR_TABLE', 'cdr'),
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Call Recording Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for call recording management.
    |
    */
    'recordings' => [
        'path' => env('RECORDING_PATH', '/var/spool/asterisk/monitor'),
        'format' => env('RECORDING_FORMAT', 'wav'),
        'retention_days' => env('RECORDING_RETENTION_DAYS', 90),
        'auto_cleanup' => true,
        'allowed_formats' => ['wav', 'mp3', 'gsm'],
    ],

    /*
    |--------------------------------------------------------------------------
    | IVR Audio Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for IVR audio file uploads and management.
    |
    */
    'ivr' => [
        'audio_path' => env('IVR_AUDIO_PATH', '/var/lib/asterisk/sounds/custom'),
        'allowed_formats' => ['wav', 'gsm', 'ulaw', 'alaw'],
        'max_file_size' => 10240, // KB
    ],

    /*
    |--------------------------------------------------------------------------
    | Asterisk Configuration Files Path
    |--------------------------------------------------------------------------
    |
    | Path to Asterisk configuration directory.
    | Used when generating config files programmatically.
    |
    */
    'config_path' => env('ASTERISK_CONFIG_PATH', '/etc/asterisk'),

    /*
    |--------------------------------------------------------------------------
    | Event Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for AMI event monitoring and processing.
    |
    */
    'events' => [
        'enabled' => true,
        'listeners' => [
            'Newchannel',
            'Newstate',
            'Hangup',
            'QueueMemberStatus',
            'QueueCallerJoin',
            'QueueCallerLeave',
            'AgentCalled',
            'AgentConnect',
            'AgentComplete',
        ],
        'buffer_size' => 1000, // Max events to buffer
        'process_async' => true, // Process events in queue
    ],

    /*
    |--------------------------------------------------------------------------
    | System Settings
    |--------------------------------------------------------------------------
    |
    | General Asterisk system configuration.
    |
    */
    'system' => [
        'timezone' => env('DEFAULT_TIMEZONE', 'UTC'),
        'default_context' => 'from-internal',
        'emergency_context' => 'emergency',
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Toggles
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features.
    |
    */
    'features' => [
        'call_recording' => env('ENABLE_CALL_RECORDING', true),
        'click_to_call' => true,
        'ivr_builder' => true,
        'queue_management' => true,
        'trunk_management' => true,
        'conference_rooms' => true,
        'voicemail' => true,
        'call_parking' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related configuration.
    |
    */
    'security' => [
        'whitelist_ips' => explode(',', env('AMI_WHITELIST_IPS', '127.0.0.1')),
        'max_login_attempts' => 5,
        'lockout_duration' => 300, // seconds
    ],
];

