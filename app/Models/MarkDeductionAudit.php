<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarkDeductionAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_attempt_id',
        'user_id',
        'original_score',
        'negative_marks',
        'final_marks',
        'reason',
    ];

    protected $casts = [
        'original_score' => 'float',
        'negative_marks' => 'float',
        'final_marks' => 'float',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
