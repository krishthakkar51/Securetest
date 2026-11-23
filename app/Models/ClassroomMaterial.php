<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ClassroomMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'user_id',
        'title',
        'description',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    /**
     * Classroom that owns this material.
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Teacher / User who uploaded this material.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Human-readable file size format.
     */
    public function formattedSize(): string
    {
        $bytes = (float) $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * Clean uppercase file extension (e.g. PDF, DOCX, PNG).
     */
    public function extension(): string
    {
        return strtoupper(pathinfo($this->original_filename, PATHINFO_EXTENSION) ?: 'FILE');
    }

    /**
     * Determine badge color based on file type.
     */
    public function typeBadgeColor(): string
    {
        $ext = strtolower($this->extension());
        return match ($ext) {
            'pdf' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300 border-rose-200 dark:border-rose-800',
            'doc', 'docx' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border-blue-200 dark:border-blue-800',
            'ppt', 'pptx' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            'xls', 'xlsx' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            'jpg', 'jpeg', 'png', 'webp' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 border-purple-200 dark:border-purple-800',
            default => 'bg-stone-100 text-stone-800 dark:bg-stone-800 dark:text-stone-300 border-stone-200 dark:border-stone-700',
        };
    }
}
