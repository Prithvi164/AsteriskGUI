# Asterisk GUI - File Structure

```
asterisk-gui/
│
├── app/
│   ├── Console/
│   │   ├── Commands/
│   │   │   ├── AmiListenerCommand.php          # AMI event listener daemon
│   │   │   ├── SyncCdrCommand.php               # Sync CDR data
│   │   │   └── CleanupRecordingsCommand.php     # Recording cleanup
│   │   └── Kernel.php
│   │
│   ├── Events/
│   │   ├── NewCallEvent.php                     # Fired when new call detected
│   │   ├── CallHangupEvent.php                  # Call ended event
│   │   ├── QueueStatusChanged.php               # Queue status update
│   │   └── AgentStatusChanged.php               # Agent status change
│   │
│   ├── Listeners/
│   │   ├── BroadcastCallEvent.php               # Broadcast to WebSocket
│   │   ├── UpdateCallStatistics.php             # Update stats cache
│   │   └── SendCallNotification.php             # Send email/SMS alerts
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   ├── LoginController.php
│   │   │   │   └── RegisterController.php
│   │   │   │
│   │   │   ├── Api/
│   │   │   │   ├── CallController.php           # Active calls API
│   │   │   │   ├── CdrController.php            # Call history API
│   │   │   │   ├── ExtensionController.php      # Extension CRUD
│   │   │   │   ├── QueueController.php          # Queue management
│   │   │   │   ├── RecordingController.php      # Recordings API
│   │   │   │   ├── IvrController.php            # IVR configuration
│   │   │   │   ├── TrunkController.php          # Trunk management
│   │   │   │   └── DashboardController.php      # Dashboard stats
│   │   │   │
│   │   │   ├── Web/
│   │   │   │   ├── DashboardController.php      # Web dashboard
│   │   │   │   ├── ExtensionController.php      # Extension management UI
│   │   │   │   ├── ReportController.php         # Reports UI
│   │   │   │   └── SettingsController.php       # Settings UI
│   │   │   │
│   │   │   └── ClickToCallController.php        # Originate calls
│   │   │
│   │   ├── Middleware/
│   │   │   ├── CheckRole.php                    # RBAC middleware
│   │   │   ├── AuditLog.php                     # Log user actions
│   │   │   └── ValidateExtension.php            # Extension validation
│   │   │
│   │   └── Requests/
│   │       ├── ExtensionRequest.php             # Extension validation rules
│   │       ├── IvrRequest.php                   # IVR validation
│   │       └── CallOriginateRequest.php         # Click-to-call validation
│   │
│   ├── Models/
│   │   ├── User.php                             # User model with roles
│   │   ├── Role.php                             # Roles
│   │   ├── Permission.php                       # Permissions
│   │   ├── Extension.php                        # Extensions
│   │   ├── Cdr.php                              # Call Detail Records
│   │   ├── Queue.php                            # Call queues
│   │   ├── QueueMember.php                      # Queue members/agents
│   │   ├── Recording.php                        # Call recordings
│   │   ├── Ivr.php                              # IVR configurations
│   │   ├── Trunk.php                            # Trunk configurations
│   │   ├── ActiveCall.php                       # Current active calls
│   │   ├── AuditLog.php                         # Audit trail
│   │   └── SystemSetting.php                    # System settings
│   │
│   ├── Services/
│   │   ├── AmiService.php                       # AMI connection & commands
│   │   ├── CallService.php                      # Call-related operations
│   │   ├── ExtensionService.php                 # Extension management logic
│   │   ├── QueueService.php                     # Queue operations
│   │   ├── RecordingService.php                 # Recording handling
│   │   ├── ReportService.php                    # Report generation
│   │   ├── IvrService.php                       # IVR logic
│   │   ├── AsteriskConfigService.php            # Generate Asterisk configs
│   │   └── NotificationService.php              # Send notifications
│   │
│   ├── Repositories/
│   │   ├── CdrRepository.php                    # CDR data access
│   │   ├── ExtensionRepository.php              # Extension data access
│   │   └── QueueRepository.php                  # Queue data access
│   │
│   ├── Jobs/
│   │   ├── ProcessAmiEvent.php                  # Process AMI events async
│   │   ├── GenerateReport.php                   # Generate scheduled reports
│   │   ├── CleanupOldRecordings.php             # Delete old recordings
│   │   └── SyncCdrData.php                      # Sync CDR periodically
│   │
│   ├── Broadcasts/
│   │   ├── CallStatusChannel.php                # WebSocket channel
│   │   └── QueueStatusChannel.php               # Queue updates channel
│   │
│   └── Exceptions/
│       ├── AmiConnectionException.php           # AMI connection errors
│       └── AsteriskCommandException.php         # Command execution errors
│
├── bootstrap/
│   ├── app.php
│   └── cache/
│
├── config/
│   ├── app.php
│   ├── database.php
│   ├── broadcasting.php
│   ├── asterisk.php                             # Custom Asterisk config
│   └── permissions.php                          # RBAC configuration
│
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_000001_create_users_table.php
│   │   ├── 2024_01_01_000002_create_roles_permissions_tables.php
│   │   ├── 2024_01_01_000003_create_extensions_table.php
│   │   ├── 2024_01_01_000004_create_cdrs_table.php
│   │   ├── 2024_01_01_000005_create_queues_table.php
│   │   ├── 2024_01_01_000006_create_queue_members_table.php
│   │   ├── 2024_01_01_000007_create_recordings_table.php
│   │   ├── 2024_01_01_000008_create_ivrs_table.php
│   │   ├── 2024_01_01_000009_create_trunks_table.php
│   │   ├── 2024_01_01_000010_create_active_calls_table.php
│   │   ├── 2024_01_01_000011_create_audit_logs_table.php
│   │   └── 2024_01_01_000012_create_system_settings_table.php
│   │
│   ├── seeders/
│   │   ├── DatabaseSeeder.php
│   │   ├── RoleSeeder.php                       # Seed default roles
│   │   ├── UserSeeder.php                       # Create admin user
│   │   └── PermissionSeeder.php                 # Seed permissions
│   │
│   └── factories/
│       ├── UserFactory.php
│       └── ExtensionFactory.php
│
├── public/
│   ├── index.php
│   ├── css/
│   │   └── app.css
│   ├── js/
│   │   └── app.js
│   ├── recordings/                              # Symlink to storage
│   └── assets/
│       ├── images/
│       └── audio/                               # IVR audio files
│
├── resources/
│   ├── js/
│   │   ├── app.js                               # Main Vue app
│   │   ├── bootstrap.js                         # Laravel Echo, Axios setup
│   │   │
│   │   ├── components/
│   │   │   ├── Dashboard/
│   │   │   │   ├── StatCard.vue                 # Stat widgets
│   │   │   │   ├── CallChart.vue                # Call volume chart
│   │   │   │   ├── ActiveCallsTable.vue         # Real-time calls table
│   │   │   │   └── QueueWidget.vue              # Queue status widget
│   │   │   │
│   │   │   ├── Calls/
│   │   │   │   ├── CallHistoryTable.vue         # CDR table
│   │   │   │   ├── CallDetailsModal.vue         # Call details popup
│   │   │   │   └── CallPlayer.vue               # Audio player
│   │   │   │
│   │   │   ├── Extensions/
│   │   │   │   ├── ExtensionList.vue            # Extension list
│   │   │   │   ├── ExtensionForm.vue            # Add/Edit extension
│   │   │   │   └── ExtensionStatus.vue          # Extension status badge
│   │   │   │
│   │   │   ├── Queues/
│   │   │   │   ├── QueueDashboard.vue           # Queue overview
│   │   │   │   ├── QueueMembers.vue             # Agent management
│   │   │   │   └── QueueStats.vue               # Queue statistics
│   │   │   │
│   │   │   ├── Ivr/
│   │   │   │   ├── IvrBuilder.vue               # Visual IVR builder
│   │   │   │   ├── IvrNode.vue                  # IVR node component
│   │   │   │   └── AudioUploader.vue            # Audio file upload
│   │   │   │
│   │   │   ├── Reports/
│   │   │   │   ├── ReportGenerator.vue          # Report builder
│   │   │   │   └── ReportChart.vue              # Charts for reports
│   │   │   │
│   │   │   └── Common/
│   │   │       ├── Sidebar.vue                  # Navigation sidebar
│   │   │       ├── Navbar.vue                   # Top navbar
│   │   │       ├── DataTable.vue                # Reusable table
│   │   │       ├── Modal.vue                    # Modal component
│   │   │       └── Notification.vue             # Toast notifications
│   │   │
│   │   ├── pages/
│   │   │   ├── Dashboard.vue                    # Main dashboard page
│   │   │   ├── ActiveCalls.vue                  # Active calls page
│   │   │   ├── CallHistory.vue                  # Call history page
│   │   │   ├── Extensions.vue                   # Extensions page
│   │   │   ├── Queues.vue                       # Queues page
│   │   │   ├── Ivrs.vue                         # IVR management
│   │   │   ├── Recordings.vue                   # Recordings page
│   │   │   ├── Reports.vue                      # Reports page
│   │   │   ├── Settings.vue                     # Settings page
│   │   │   └── Users.vue                        # User management
│   │   │
│   │   ├── store/
│   │   │   ├── index.js                         # Vuex/Pinia store
│   │   │   ├── modules/
│   │   │   │   ├── calls.js                     # Call state
│   │   │   │   ├── extensions.js                # Extensions state
│   │   │   │   ├── queues.js                    # Queue state
│   │   │   │   └── auth.js                      # Auth state
│   │   │   └── ...
│   │   │
│   │   ├── router/
│   │   │   └── index.js                         # Vue Router config
│   │   │
│   │   └── utils/
│   │       ├── api.js                           # API helper functions
│   │       ├── formatters.js                    # Data formatters
│   │       └── validators.js                    # Form validators
│   │
│   ├── css/
│   │   └── app.css                              # Tailwind/Bootstrap
│   │
│   └── views/
│       ├── layouts/
│       │   ├── app.blade.php                    # Main layout
│       │   └── guest.blade.php                  # Guest layout
│       │
│       ├── auth/
│       │   ├── login.blade.php                  # Login page
│       │   └── register.blade.php               # Register page
│       │
│       ├── dashboard.blade.php                  # Dashboard (Vue mounts here)
│       └── welcome.blade.php                    # Landing page
│
├── routes/
│   ├── web.php                                  # Web routes
│   ├── api.php                                  # API routes
│   ├── channels.php                             # Broadcast channels
│   └── console.php                              # Artisan commands
│
├── storage/
│   ├── app/
│   │   ├── recordings/                          # Call recordings
│   │   ├── ivr-audio/                           # IVR audio files
│   │   └── reports/                             # Generated reports
│   ├── framework/
│   ├── logs/
│   │   ├── laravel.log
│   │   ├── ami-events.log                       # AMI event logs
│   │   └── audit.log                            # Audit trail
│   └── cache/
│
├── tests/
│   ├── Feature/
│   │   ├── CallManagementTest.php
│   │   ├── ExtensionManagementTest.php
│   │   ├── QueueManagementTest.php
│   │   └── AuthenticationTest.php
│   │
│   └── Unit/
│       ├── AmiServiceTest.php
│       ├── CallServiceTest.php
│       └── ExtensionServiceTest.php
│
├── .env.example                                 # Environment template
├── .gitignore
├── artisan                                      # Laravel CLI
├── composer.json                                # PHP dependencies
├── package.json                                 # NPM dependencies
├── phpunit.xml                                  # PHPUnit config
├── vite.config.js                               # Vite configuration
├── tailwind.config.js                           # Tailwind CSS config
├── docker-compose.yml                           # Docker setup (optional)
├── Dockerfile                                   # Docker image (optional)
└── README.md                                    # Project documentation
```

## Key Design Patterns Used

1. **Repository Pattern**: Data access abstraction (CdrRepository, etc.)
2. **Service Pattern**: Business logic separation (AmiService, CallService)
3. **Observer Pattern**: Event listeners for AMI events
4. **Factory Pattern**: Model factories for testing
5. **Singleton Pattern**: AMI connection management
6. **Strategy Pattern**: Different report generation strategies
7. **Decorator Pattern**: Middleware for request processing

## Directory Responsibilities

- **app/Services**: Business logic, reusable across controllers
- **app/Repositories**: Data access layer, database queries
- **app/Events**: Domain events that occur in the system
- **app/Listeners**: React to events (logging, notifications, etc.)
- **app/Jobs**: Background tasks, queued operations
- **app/Http/Controllers/Api**: REST API endpoints
- **app/Http/Controllers/Web**: Web page controllers
- **resources/js/components**: Reusable Vue components
- **resources/js/pages**: Full page Vue components
- **config/asterisk.php**: Asterisk-specific configuration

