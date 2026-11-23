<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_attempt_id',
        'question_id',
        'selected_option_id',
        'text_answer',
        'is_correct',
        'marks_awarded',
        'teacher_feedback',
        'is_graded',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'marks_awarded' => 'float',
        'is_graded' => 'boolean',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'selected_option_id');
    }

    public function isGraded(): bool
    {
        return (bool) $this->is_graded;
    }

    public function hasFeedback(): bool
    {
        return !empty($this->teacher_feedback);
    }
}
