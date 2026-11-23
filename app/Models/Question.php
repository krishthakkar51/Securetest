<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    public const TYPE_MCQ = 'mcq';
    public const TYPE_TEXT = 'text';

    protected $fillable = [
        'test_id',
        'question_type',
        'question_text',
        'marks',
        'order',
    ];

    protected $casts = [
        'marks' => 'integer',
        'order' => 'integer',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order');
    }

    public function studentAnswers(): HasMany
    {
        return $this->hasMany(StudentAnswer::class);
    }

    public function isMcq(): bool
    {
        return $this->question_type === self::TYPE_MCQ;
    }

    public function isText(): bool
    {
        return $this->question_type === self::TYPE_TEXT;
    }

    /**
     * Get the correct option for MCQ.
     */
    public function correctOption()
    {
        return $this->options()->where('is_correct', true)->first();
    }
}
