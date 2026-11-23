<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassroomTest;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the student dashboard.
     * Categorizes assigned tests into: Available, Upcoming, Submitted, and Past Deadline.
     */
    public function index(): View
    {
        $student = Auth::user();

        // Enrolled classrooms
        $enrolledClassrooms = $student->enrolledClassrooms()
            ->with('teacher')
            ->withCount(['students', 'tests'])
            ->latest('classrooms.created_at')
            ->get();

        $enrolledClassroomIds = $enrolledClassrooms->pluck('id');

        // All active tests assigned to enrolled classrooms
        $allAssignedTests = ClassroomTest::whereIn('classroom_id', $enrolledClassroomIds)
            ->where('is_active', true)
            ->with(['classroom.teacher', 'test.questions'])
            ->latest()
            ->get();

        // Student's exam attempts
        $myAttempts = ExamAttempt::where('user_id', $student->id)
            ->with(['test', 'classroom'])
            ->latest()
            ->get()
            ->keyBy(fn ($attempt) => $attempt->classroom_id . '_' . $attempt->test_id);

        // Group tests into the 4 required sections
        $availableTests = collect();
        $upcomingTests = collect();
        $submittedTests = collect();
        $pastDeadlineTests = collect();

        foreach ($allAssignedTests as $assignment) {
            $attemptKey = $assignment->classroom_id . '_' . $assignment->test_id;
            $attempt = $myAttempts[$attemptKey] ?? null;

            if ($attempt && $attempt->isSubmitted()) {
                $submittedTests->push($assignment);
            } elseif ($assignment->isUpcoming()) {
                $upcomingTests->push($assignment);
            } elseif ($assignment->isPastDeadline()) {
                $pastDeadlineTests->push($assignment);
            } else {
                $availableTests->push($assignment);
            }
        }

        $managedClassrooms = \App\Models\Classroom::where('user_id', $student->id)
            ->with('teacher')
            ->withCount(['students', 'tests'])
            ->latest()
            ->get();

        $enrolledIds = $enrolledClassrooms->pluck('id')->all();
        $managedCards = $managedClassrooms->filter(fn ($c) => !in_array($c->id, $enrolledIds));

        // Upcoming work: Only unsubmitted assignments due within the next 48 hours
        $now = now();
        $in48Hours = $now->copy()->addHours(48);

        $upcomingWork = $allAssignedTests->filter(function ($assignment) use ($myAttempts, $now, $in48Hours) {
            if (!$assignment->due_at) {
                return false;
            }
            if ($assignment->due_at->isPast() || $assignment->due_at->gt($in48Hours)) {
                return false;
            }
            $attempt = $myAttempts[$assignment->classroom_id . '_' . $assignment->test_id] ?? null;
            return !$attempt || !$attempt->isSubmitted();
        })->sortBy('due_at')->values();

        $sidebarClassrooms = $enrolledClassrooms->concat($managedCards);

        return view('student.dashboard', compact(
            'enrolledClassrooms',
            'managedClassrooms',
            'managedCards',
            'sidebarClassrooms',
            'upcomingWork',
            'availableTests',
            'upcomingTests',
            'submittedTests',
            'pastDeadlineTests',
            'myAttempts'
        ));
    }
}
