<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassroomComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_post_id',
        'user_id',
        'parent_id',
        'content',
    ];

    /**
     * The classroom post this comment belongs to.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(ClassroomPost::class, 'classroom_post_id');
    }

    /**
     * Author of the comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Parent comment if this is a reply.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ClassroomComment::class, 'parent_id');
    }

    /**
     * Nested replies to this comment.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ClassroomComment::class, 'parent_id')->oldest();
    }

    /**
     * Check if a user can edit this comment.
     * Only the original author can edit the comment text.
     */
    public function canEdit(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->user_id === $user->id;
    }

    /**
     * Check if a user can delete this comment.
     * The author can delete their own comment, OR the classroom instructor (moderator) can delete any comment.
     */
    public function canDelete(?User $user, ?Classroom $classroom = null): bool
    {
        if (!$user) {
            return false;
        }

        // Author can always delete their own comment
        if ($this->user_id === $user->id) {
            return true;
        }

        // Classroom owner / instructor has moderation rights
        $class = $classroom ?: ($this->post ? $this->post->classroom : null);
        if ($class && $class->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if this comment was authored by the classroom instructor.
     */
    public function isInstructorComment(?Classroom $classroom = null): bool
    {
        $class = $classroom ?: ($this->post ? $this->post->classroom : null);
        if (!$class) {
            return false;
        }

        return $this->user_id === $class->user_id;
    }
}
