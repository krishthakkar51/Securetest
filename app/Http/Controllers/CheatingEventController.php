<?php

namespace App\Http\Controllers;

use App\Models\CheatingEvent;
use App\Models\ExamAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CheatingEventController extends Controller
{
    public const MAX_ATTEMPT_SCREENSHOTS = 6;

    /**
     * Store a suspicious proctoring event and optional evidence screenshot.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'event_type' => ['required', 'string', 'max:100'],
            'suspicion_score' => ['nullable', 'integer', 'min:0'],
            'exam_attempt_id' => ['nullable', 'integer', 'exists:exam_attempts,id'],
            'evidence' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $user = auth()->user();

        $defaultScores = [
            'tab_switch' => 1,
            'fullscreen_exit' => 5,
            'fullscreen_timeout' => 10,
            'no_face_detected' => 2,
            'multiple_faces_detected' => 3,
            'abnormal_eye_movement' => 1,
            'gaze_away_left' => 1,
            'gaze_away_right' => 1,
            'gaze_away_up' => 1,
            'gaze_away_down' => 1,
            'eyes_not_detected' => 2,
            'eyes_covered_object' => 2,
            'face_normal' => 0,
        ];

        $eventScore = $request->filled('suspicion_score')
            ? (int) $request->suspicion_score
            : ($defaultScores[$request->event_type] ?? 1);

        // =========================================
        // RESOLVE FACE DETECTION EVENT
        // =========================================
        if ($request->event_type === 'face_normal') {
            $eventQuery = $user->cheatingEvents()
                ->whereIn('event_type', ['no_face_detected', 'multiple_faces_detected'])
                ->whereNull('resolved_at');

            if ($request->filled('exam_attempt_id')) {
                $eventQuery->where('exam_attempt_id', $request->exam_attempt_id);
            }

            $event = $eventQuery->latest('occurred_at')->first();

            if ($event) {
                $event->update([
                    'resolved_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'event_resolved' => true,
                    'resolved_event' => $event->event_type,
                ]);
            }

            return response()->json([
                'success' => true,
                'event_resolved' => false,
            ]);
        }

        $storedEvidencePath = null;
        $cheatingEvent = null;
        $screenshotCount = 0;
        $quotaExhausted = false;

        try {
            DB::transaction(function () use (
                $request,
                $user,
                $eventScore,
                &$storedEvidencePath,
                &$cheatingEvent,
                &$screenshotCount,
                &$quotaExhausted
            ) {
                $attempt = null;
                if ($request->filled('exam_attempt_id')) {
                    // Lock the parent exam_attempt record inside the transaction using lockForUpdate
                    $attempt = ExamAttempt::where('id', $request->exam_attempt_id)
                        ->lockForUpdate()
                        ->first();
                } else {
                    // Fallback for legacy calls without exam_attempt_id: lock user record
                    $user->lockForUpdate()->first();
                }

                // Count existing screenshots within the locked transaction
                $screenshotQuery = CheatingEvent::whereNotNull('evidence_path');
                if ($attempt) {
                    $screenshotQuery->where('exam_attempt_id', $attempt->id);
                } else {
                    $screenshotQuery->where('user_id', $user->id);
                }
                $existingScreenshots = $screenshotQuery->count();

                $quotaExhausted = ($existingScreenshots >= self::MAX_ATTEMPT_SCREENSHOTS);

                // Attempt to store evidence ONLY if under the 6-screenshot limit
                if ($request->hasFile('evidence') && !$quotaExhausted) {
                    try {
                        $storedEvidencePath = $request->file('evidence')->store('evidence', 'public');
                        if ($storedEvidencePath) {
                            $existingScreenshots++;
                        }
                    } catch (Throwable $uploadException) {
                        Log::warning('Evidence screenshot upload failed: ' . $uploadException->getMessage());
                        $storedEvidencePath = null;
                    }
                }

                $screenshotCount = $existingScreenshots;
                $quotaExhausted = ($screenshotCount >= self::MAX_ATTEMPT_SCREENSHOTS);

                // Always create cheating event, preserving event logging and scores
                $cheatingEvent = CheatingEvent::create([
                    'user_id' => $user->id,
                    'exam_attempt_id' => $request->exam_attempt_id,
                    'event_type' => $request->event_type,
                    'suspicion_score' => $eventScore,
                    'occurred_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'evidence_path' => $storedEvidencePath,
                ]);
            });
        } catch (Throwable $e) {
            // Cleanup stored file if database transaction failed
            if ($storedEvidencePath && Storage::disk('public')->exists($storedEvidencePath)) {
                Storage::disk('public')->delete($storedEvidencePath);
            }
            throw $e;
        }

        // Calculate total user suspicion score
        $newScore = $user->cheatingEvents()->sum('suspicion_score');

        return response()->json([
            'success' => true,
            'event_id' => $cheatingEvent ? $cheatingEvent->id : null,
            'total_score' => $newScore,
            'evidence_path' => $storedEvidencePath,
            'screenshot_count' => $screenshotCount,
            'screenshot_quota_exhausted' => $quotaExhausted,
        ]);
    }
}