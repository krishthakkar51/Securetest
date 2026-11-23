<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassroomTest extends Model
{
    use HasFactory;

    protected $table = 'classroom_test';

    protected $fillable = [
        'classroom_id',
        'test_id',
        'starts_at',
        'due_at',
        'allow_late_submissions',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'due_at' => 'datetime',
        'allow_late_submissions' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    /**
     * Check if the test is currently open and can be attempted by a student.
     */
    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        // If test has a start time in the future, it is not yet available
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        // If test has passed deadline
        if ($this->due_at && $now->gt($this->due_at)) {
            // Can only be attempted if late submissions are explicitly allowed
            return (bool) $this->allow_late_submissions;
        }

        return true;
    }

    /**
     * Check if test is scheduled for the future.
     */
    public function isUpcoming(): bool
    {
        return $this->starts_at && now()->lt($this->starts_at);
    }

    /**
     * Check if the deadline has passed.
     */
    public function isPastDeadline(): bool
    {
        return $this->due_at && now()->gt($this->due_at);
    }

    /**
     * Check if late attempts are currently permitted.
     */
    public function isLateAllowed(): bool
    {
        return $this->isPastDeadline() && (bool) $this->allow_late_submissions;
    }

    /**
     * Status label for display.
     */
    public function status(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        if ($this->isUpcoming()) {
            return 'upcoming';
        }

        if ($this->isPastDeadline()) {
            return $this->allow_late_submissions ? 'late_allowed' : 'past_deadline';
        }

        return 'available';
    }
}
