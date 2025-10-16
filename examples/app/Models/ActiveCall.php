<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Active Call Model
 * 
 * Represents currently active calls in the system
 * Updated in real-time by AMI event listener
 */
class ActiveCall extends Model
{
    protected $table = 'active_calls';

    protected $fillable = [
        'channel',
        'unique_id',
        'caller_id_num',
        'caller_id_name',
        'connected_line_num',
        'connected_line_name',
        'destination',
        'extension_id',
        'user_id',
        'context',
        'status',
        'application',
        'application_data',
        'started_at',
        'answered_at',
        'bridge_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'answered_at' => 'datetime',
    ];

    /**
     * Get the extension associated with this call
     */
    public function extension(): BelongsTo
    {
        return $this->belongsTo(Extension::class);
    }

    /**
     * Get the user associated with this call
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get call duration in seconds
     */
    public function getDurationAttribute(): int
    {
        if (!$this->started_at) {
            return 0;
        }

        $endTime = $this->answered_at ?? now();
        return $this->started_at->diffInSeconds($endTime);
    }

    /**
     * Get formatted duration (HH:MM:SS)
     */
    public function getFormattedDurationAttribute(): string
    {
        $seconds = $this->duration;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'ringing' => 'warning',
            'up' => 'success',
            'busy' => 'danger',
            'down' => 'secondary',
            default => 'info',
        };
    }

    /**
     * Check if call is answered
     */
    public function isAnswered(): bool
    {
        return $this->status === 'up' && $this->answered_at !== null;
    }

    /**
     * Check if call is ringing
     */
    public function isRinging(): bool
    {
        return $this->status === 'ringing';
    }

    /**
     * Scope to get only ringing calls
     */
    public function scopeRinging($query)
    {
        return $query->where('status', 'ringing');
    }

    /**
     * Scope to get only answered calls
     */
    public function scopeAnswered($query)
    {
        return $query->where('status', 'up')->whereNotNull('answered_at');
    }

    /**
     * Scope to get calls by extension
     */
    public function scopeByExtension($query, $extensionId)
    {
        return $query->where('extension_id', $extensionId);
    }

    /**
     * Scope to get calls by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}

