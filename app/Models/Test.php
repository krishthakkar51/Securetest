<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Test extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'duration_minutes',
        'total_marks',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'duration_minutes' => 'integer',
        'total_marks' => 'integer',
    ];

    /**
     * The teacher who created the test.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Questions belonging to this test.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    /**
     * Student attempts for this test.
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    /**
     * Classrooms where this test is assigned.
     */
    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'classroom_test')
            ->withPivot(['id', 'starts_at', 'due_at', 'allow_late_submissions', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Classroom test assignments.
     */
    public function classroomTests(): HasMany
    {
        return $this->hasMany(ClassroomTest::class);
    }

    /**
     * Count of MCQ questions.
     */
    public function mcqCount(): int
    {
        return $this->questions()->where('question_type', Question::TYPE_MCQ)->count();
    }

    /**
     * Count of Text-answer questions.
     */
    public function textCount(): int
    {
        return $this->questions()->where('question_type', Question::TYPE_TEXT)->count();
    }
}
