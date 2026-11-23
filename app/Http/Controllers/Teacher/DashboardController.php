<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\CheatingEvent;
use App\Models\Classroom;
use App\Models\ExamAttempt;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the teacher dashboard.
     */
    public function index(): View
    {
        $user = Auth::user();
        $teacherId = $user->id;

        $classroomsCount = Classroom::where('user_id', $teacherId)->count();
        $testsCount = Test::where('user_id', $teacherId)->count();
        $submissionsCount = ExamAttempt::whereHas('test', fn ($q) => $q->where('user_id', $teacherId))->count();
        $proctoringAlertsCount = CheatingEvent::count();

        $recentClassrooms = Classroom::where('user_id', $teacherId)
            ->withCount(['students', 'tests'])
            ->latest()
            ->take(4)
            ->get();

        $recentTests = Test::where('user_id', $teacherId)
            ->withCount('questions')
            ->withCount('attempts')
            ->latest()
            ->take(5)
            ->get();

        // Enrolled classrooms (if teacher joined someone else's classroom)
        $enrolledClassrooms = $user->enrolledClassrooms()
            ->with('teacher')
            ->withCount(['students', 'tests'])
            ->get();

        // Managed classrooms (created by this user)
        $managedClassrooms = Classroom::where('user_id', $teacherId)
            ->with('teacher')
            ->withCount(['students', 'tests'])
            ->latest()
            ->get();

        // Avoid duplicates in cards (display enrolled first, then managed not already enrolled)
        $enrolledIds = $enrolledClassrooms->pluck('id')->all();
        $managedCards = $managedClassrooms->filter(fn ($c) => !in_array($c->id, $enrolledIds));

        // Upcoming work from enrolled classrooms
        $enrolledClassroomIds = $enrolledClassrooms->pluck('id');
        $myAttempts = ExamAttempt::where('user_id', $user->id)
            ->get()
            ->keyBy(fn ($a) => $a->classroom_id . '_' . $a->test_id);

        $upcomingWork = \App\Models\ClassroomTest::whereIn('classroom_id', $enrolledClassroomIds)
            ->where('is_active', true)
            ->with(['classroom', 'test'])
            ->get()
            ->filter(function ($assignment) use ($myAttempts) {
                $attempt = $myAttempts[$assignment->classroom_id . '_' . $assignment->test_id] ?? null;
                return !$attempt || !$attempt->isSubmitted();
            })
            ->sortBy(function ($assignment) {
                return $assignment->due_at ?? now()->addYears(10);
            })
            ->values();

        $sidebarClassrooms = $enrolledClassrooms->concat($managedCards);

        return view('teacher.dashboard', compact(
            'classroomsCount',
            'testsCount',
            'submissionsCount',
            'proctoringAlertsCount',
            'recentClassrooms',
            'recentTests',
            'enrolledClassrooms',
            'managedClassrooms',
            'managedCards',
            'upcomingWork',
            'sidebarClassrooms'
        ));
    }
}
