<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassroomPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'user_id',
        'title',
        'content',
    ];

    /**
     * The classroom this post belongs to.
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Author of the post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Top-level comments on this post (parent_id is null).
     */
    public function comments(): HasMany
    {
        return $this->hasMany(ClassroomComment::class)->whereNull('parent_id')->oldest();
    }

    /**
     * All comments and replies on this post.
     */
    public function allComments(): HasMany
    {
        return $this->hasMany(ClassroomComment::class);
    }

    /**
     * Check if a given user is allowed to edit this post.
     * Only the original author can edit the post content.
     */
    public function canEdit(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->user_id === $user->id;
    }

    /**
     * Check if a given user is allowed to delete this post.
     * The original author OR the classroom instructor (moderator) can delete it.
     */
    public function canDelete(?User $user, ?Classroom $classroom = null): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->user_id === $user->id) {
            return true;
        }

        $class = $classroom ?: $this->classroom;
        if ($class && $class->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if this post is an announcement published by the classroom instructor.
     */
    public function isInstructorAnnouncement(): bool
    {
        $classroom = $this->classroom;
        if (!$classroom) {
            return false;
        }

        return $this->user_id === $classroom->user_id;
    }
}
