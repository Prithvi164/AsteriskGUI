# API Examples

This document provides practical examples of using the Asterisk PBX Management GUI API.

## Authentication

### Login and Get Token

```bash
curl -X POST http://asterisk-gui.local/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@asterisk-gui.local",
    "password": "password"
  }'
```

**Response:**
```json
{
  "success": true,
  "token": "1|AbCdEfGhIjKlMnOpQrStUvWxYz",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@asterisk-gui.local",
    "role": "admin"
  }
}
```

## Active Calls

### Get All Active Calls

```bash
curl -X GET http://asterisk-gui.local/api/calls/active \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "channel": "SIP/100-00000001",
      "caller_id": "5551234567",
      "caller_name": "John Doe",
      "destination": "5559876543",
      "extension": "100",
      "status": "up",
      "duration": 125,
      "started_at": "2024-01-15T10:30:00Z",
      "user": {
        "id": 5,
        "name": "John Doe"
      }
    }
  ],
  "count": 1
}
```

### Get Call Statistics

```bash
curl -X GET http://asterisk-gui.local/api/calls/stats?period=today \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_calls": 245,
    "answered": 198,
    "missed": 47,
    "average_duration": 180,
    "total_duration": 35640,
    "peak_hour": "14:00"
  }
}
```

### Originate Call (Click-to-Call)

```bash
curl -X POST http://asterisk-gui.local/api/calls/originate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "extension": "100",
    "destination": "5551234567",
    "context": "from-internal",
    "timeout": 30
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "Call initiated successfully",
  "data": {
    "extension": "100",
    "destination": "5551234567"
  }
}
```

### Hangup Call

```bash
curl -X POST http://asterisk-gui.local/api/calls/SIP%2F100-00000001/hangup \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "message": "Call terminated successfully"
}
```

## Call History (CDR)

### Get Call History

```bash
curl -X GET "http://asterisk-gui.local/api/cdr?from=2024-01-01&to=2024-01-31&page=1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1234,
      "caller_id": "5551234567",
      "destination": "5559876543",
      "calldate": "2024-01-15T14:30:00Z",
      "duration": 180,
      "billsec": 175,
      "disposition": "ANSWERED",
      "recording": "/recordings/2024/01/15/call-1234.wav",
      "extension": {
        "id": 5,
        "number": "100",
        "name": "John Doe"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 245,
    "per_page": 20,
    "last_page": 13
  }
}
```

### Export Call History to CSV

```bash
curl -X GET "http://asterisk-gui.local/api/cdr/export/csv?from=2024-01-01&to=2024-01-31" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  --output call-history.csv
```

### Get CDR Statistics

```bash
curl -X GET "http://asterisk-gui.local/api/cdr/statistics?from=2024-01-01&to=2024-01-31" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_calls": 1250,
    "answered": 1050,
    "no_answer": 150,
    "busy": 50,
    "failed": 0,
    "average_duration": 195,
    "total_billable_seconds": 204750,
    "by_disposition": {
      "ANSWERED": 1050,
      "NO ANSWER": 150,
      "BUSY": 50
    },
    "by_day": [
      {"date": "2024-01-01", "count": 42},
      {"date": "2024-01-02", "count": 38}
    ]
  }
}
```

## Extensions

### Get All Extensions

```bash
curl -X GET http://asterisk-gui.local/api/extensions \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "number": "100",
      "name": "John Doe",
      "email": "john@example.com",
      "technology": "SIP",
      "is_active": true,
      "voicemail_enabled": true,
      "dnd": false,
      "last_registered": "2024-01-15T10:25:00Z"
    }
  ]
}
```

### Create Extension

```bash
curl -X POST http://asterisk-gui.local/api/extensions \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "number": "101",
    "name": "Jane Smith",
    "email": "jane@example.com",
    "secret": "SecurePassword123",
    "technology": "SIP",
    "context": "from-internal",
    "voicemail_enabled": true,
    "voicemail_pin": "1234",
    "record_calls": false
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "Extension created successfully",
  "data": {
    "id": 2,
    "number": "101",
    "name": "Jane Smith",
    "is_active": true
  }
}
```

### Update Extension

```bash
curl -X PUT http://asterisk-gui.local/api/extensions/2 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Doe",
    "email": "jane.doe@example.com",
    "dnd": true
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "Extension updated successfully"
}
```

### Delete Extension

```bash
curl -X DELETE http://asterisk-gui.local/api/extensions/2 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "message": "Extension deleted successfully"
}
```

### Get Extension Status

```bash
curl -X GET http://asterisk-gui.local/api/extensions/1/status \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "extension": "100",
    "registered": true,
    "ip_address": "192.168.1.100",
    "port": 5060,
    "user_agent": "Zoiper/5.5.1",
    "last_registered": "2024-01-15T10:25:00Z"
  }
}
```

## Queues

### Get All Queues

```bash
curl -X GET http://asterisk-gui.local/api/queues \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "support",
      "description": "Customer Support Queue",
      "strategy": "ringall",
      "timeout": 15,
      "is_active": true
    }
  ]
}
```

### Get Queue Status

```bash
curl -X GET http://asterisk-gui.local/api/queues/1/status \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "name": "support",
    "calls_waiting": 3,
    "average_hold_time": 45,
    "completed_today": 125,
    "abandoned_today": 8,
    "service_level_perf": 92.5,
    "members": [
      {
        "extension": "100",
        "name": "John Doe",
        "status": "available",
        "paused": false,
        "calls_taken": 15,
        "last_call": "2024-01-15T14:25:00Z"
      }
    ],
    "waiting_callers": [
      {
        "caller_id": "5551234567",
        "position": 1,
        "wait_time": 32
      }
    ]
  }
}
```

### Create Queue

```bash
curl -X POST http://asterisk-gui.local/api/queues \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "sales",
    "description": "Sales Team Queue",
    "strategy": "leastrecent",
    "timeout": 20,
    "maxlen": 10,
    "announce_holdtime": true,
    "servicelevel": 60
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "Queue created successfully",
  "data": {
    "id": 2,
    "name": "sales"
  }
}
```

### Add Member to Queue

```bash
curl -X POST http://asterisk-gui.local/api/queues/1/members \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "extension_id": 5,
    "penalty": 0
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "Member added to queue successfully"
}
```

### Pause Queue Member

```bash
curl -X POST http://asterisk-gui.local/api/queues/1/members/5/pause \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "Break"
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "Member paused successfully"
}
```

## Call Recordings

### Get All Recordings

```bash
curl -X GET http://asterisk-gui.local/api/recordings \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "filename": "call-1234.wav",
      "caller_id": "5551234567",
      "destination": "5559876543",
      "duration": 180,
      "date": "2024-01-15T14:30:00Z",
      "size": 2048576,
      "format": "wav"
    }
  ]
}
```

### Download Recording

```bash
curl -X GET http://asterisk-gui.local/api/recordings/1/download \
  -H "Authorization: Bearer YOUR_TOKEN" \
  --output recording.wav
```

### Stream Recording (for web player)

```bash
curl -X GET http://asterisk-gui.local/api/recordings/1/stream \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Returns audio stream.

## Dashboard

### Get Dashboard Statistics

```bash
curl -X GET http://asterisk-gui.local/api/dashboard/stats \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "active_calls": 12,
    "registered_extensions": 45,
    "total_extensions": 50,
    "calls_today": 245,
    "answered_today": 198,
    "missed_today": 47,
    "average_duration_today": 180,
    "active_queue_calls": 3,
    "system_uptime": "15 days, 4 hours"
  }
}
```

### Get Recent Calls

```bash
curl -X GET http://asterisk-gui.local/api/dashboard/recent-calls?limit=10 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "caller_id": "5551234567",
      "destination": "5559876543",
      "duration": 180,
      "disposition": "ANSWERED",
      "calldate": "2024-01-15T14:30:00Z"
    }
  ]
}
```

## Error Handling

All API errors follow this format:

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

### Common HTTP Status Codes

- `200 OK` - Request successful
- `201 Created` - Resource created successfully
- `400 Bad Request` - Invalid request data
- `401 Unauthorized` - Authentication required or failed
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation failed
- `500 Internal Server Error` - Server error

## WebSocket Integration

### JavaScript Example

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true,
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            Authorization: 'Bearer ' + authToken
        }
    }
});

// Listen for new call events
window.Echo.channel('calls')
    .listen('NewCallEvent', (event) => {
        console.log('New call:', event.call);
        // Update UI with new call
    })
    .listen('CallHangupEvent', (event) => {
        console.log('Call ended:', event.call);
        // Remove call from UI
    });

// Listen for queue updates
window.Echo.channel('queues')
    .listen('QueueStatusChanged', (event) => {
        console.log('Queue updated:', event.queue);
        // Update queue statistics
    });
```

## PHP Client Example

```php
<?php

use GuzzleHttp\Client;

class AsteriskGuiClient
{
    protected $client;
    protected $token;
    
    public function __construct($baseUrl, $email, $password)
    {
        $this->client = new Client(['base_uri' => $baseUrl]);
        $this->authenticate($email, $password);
    }
    
    protected function authenticate($email, $password)
    {
        $response = $this->client->post('/api/auth/login', [
            'json' => [
                'email' => $email,
                'password' => $password
            ]
        ]);
        
        $data = json_decode($response->getBody(), true);
        $this->token = $data['token'];
    }
    
    public function getActiveCalls()
    {
        $response = $this->client->get('/api/calls/active', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token
            ]
        ]);
        
        return json_decode($response->getBody(), true);
    }
    
    public function originateCall($extension, $destination)
    {
        $response = $this->client->post('/api/calls/originate', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token
            ],
            'json' => [
                'extension' => $extension,
                'destination' => $destination
            ]
        ]);
        
        return json_decode($response->getBody(), true);
    }
}

// Usage
$client = new AsteriskGuiClient(
    'http://asterisk-gui.local',
    'admin@asterisk-gui.local',
    'password'
);

$activeCalls = $client->getActiveCalls();
print_r($activeCalls);
```

## Python Client Example

```python
import requests

class AsteriskGuiClient:
    def __init__(self, base_url, email, password):
        self.base_url = base_url
        self.token = None
        self.authenticate(email, password)
    
    def authenticate(self, email, password):
        response = requests.post(
            f"{self.base_url}/api/auth/login",
            json={"email": email, "password": password}
        )
        data = response.json()
        self.token = data['token']
    
    def get_headers(self):
        return {"Authorization": f"Bearer {self.token}"}
    
    def get_active_calls(self):
        response = requests.get(
            f"{self.base_url}/api/calls/active",
            headers=self.get_headers()
        )
        return response.json()
    
    def originate_call(self, extension, destination):
        response = requests.post(
            f"{self.base_url}/api/calls/originate",
            headers=self.get_headers(),
            json={"extension": extension, "destination": destination}
        )
        return response.json()

# Usage
client = AsteriskGuiClient(
    "http://asterisk-gui.local",
    "admin@asterisk-gui.local",
    "password"
)

active_calls = client.get_active_calls()
print(active_calls)
```

## Rate Limiting

The API implements rate limiting to prevent abuse:

- **Authenticated users**: 60 requests per minute
- **Unauthenticated**: 10 requests per minute

Rate limit headers are included in responses:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1610723400
```

When rate limit is exceeded, you'll receive a `429 Too Many Requests` response.

---

For more information, see the [main documentation](README.md).

