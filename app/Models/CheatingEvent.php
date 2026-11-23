<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheatingEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'exam_attempt_id',
        'event_type',
        'suspicion_score',
        'occurred_at',
        'resolved_at',
        'ip_address',
        'user_agent',
        'evidence_path',
    ];

    protected $casts = [
        'suspicion_score' => 'integer',
        'occurred_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function hasEvidence(): bool
    {
        return !empty($this->evidence_path);
    }
}
