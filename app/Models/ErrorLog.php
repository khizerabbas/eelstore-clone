<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

// One grouped error (see App\Support\ErrorRecorder). Shown in Settings → Error log.
class ErrorLog extends Model
{
    use Prunable;

    protected $fillable = [
        'fingerprint',
        'source',
        'level',
        'type',
        'message',
        'file',
        'line',
        'trace',
        'url',
        'method',
        'context',
        'user_id',
        'ip_address',
        'user_agent',
        'occurrences',
        'first_seen_at',
        'last_seen_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'context'       => 'array',
            'first_seen_at' => 'datetime',
            'last_seen_at'  => 'datetime',
            'resolved_at'   => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Removed by the daily `model:prune` once not seen for ERROR_LOG_RETENTION_DAYS (default 30)
    public function prunable(): Builder
    {
        return static::where('last_seen_at', '<', now()->subDays((int) config('errorlog.retention_days', 30)));
    }
}
