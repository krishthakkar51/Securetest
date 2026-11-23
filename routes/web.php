<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CheatingEventController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Teacher\TestController as TeacherTestController;
use App\Http\Controllers\Teacher\ClassroomController as TeacherClassroomController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\ClassroomController as StudentClassroomController;
use App\Http\Controllers\ClassroomPostController;
use App\Http\Controllers\ClassroomCommentController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $user = auth()->user();
    if ($user->isTeacher()) {
        return redirect()->route('teacher.dashboard');
    }
    return redirect()->route('student.dashboard');
})->middleware(['auth', 'active.session'])->name('dashboard');

Route::middleware(['auth', 'active.session', 'role:teacher'])
    ->prefix('teacher')
    ->name('teacher.')
    ->group(function () {
        Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');

        // Standalone Tests Management
        Route::get('/tests', [TeacherTestController::class, 'index'])->name('tests.index');
        Route::get('/tests/create', [TeacherTestController::class, 'create'])->name('tests.create');
        Route::post('/tests', [TeacherTestController::class, 'store'])->name('tests.store');
        Route::get('/tests/{test}', [TeacherTestController::class, 'show'])->name('tests.show');
    });

Route::middleware(['auth', 'active.session', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    });

// Ownership-based Classroom & Test Management (Accessible to any authenticated user under Option A)
Route::middleware(['auth', 'active.session'])
    ->prefix('teacher')
    ->name('teacher.')
    ->group(function () {
        // Classrooms Management
        Route::get('/classrooms', [TeacherClassroomController::class, 'index'])->name('classrooms.index');
        Route::get('/classrooms/create', [TeacherClassroomController::class, 'create'])->name('classrooms.create');
        Route::post('/classrooms', [TeacherClassroomController::class, 'store'])->name('classrooms.store');
        Route::get('/classrooms/{classroom}', [TeacherClassroomController::class, 'show'])->name('classrooms.show');
        Route::get('/classrooms/{classroom}/edit', [TeacherClassroomController::class, 'edit'])->name('classrooms.edit');
        Route::put('/classrooms/{classroom}', [TeacherClassroomController::class, 'update'])->name('classrooms.update');
        Route::post('/classrooms/{classroom}/join-requests/{joinRequest}', [TeacherClassroomController::class, 'updateJoinRequestStatus'])->name('classrooms.join-requests.update');
        Route::delete('/classrooms/{classroom}/students/{student}', [TeacherClassroomController::class, 'removeStudent'])->name('classrooms.students.remove');
        
        // Direct classroom test creation
        Route::get('/classrooms/{classroom}/tests/create', [TeacherClassroomController::class, 'createTest'])->name('classrooms.tests.create');
        Route::post('/classrooms/{classroom}/tests/create', [TeacherClassroomController::class, 'storeTest'])->name('classrooms.tests.store-direct');

        // Study materials management
        Route::post('/classrooms/{classroom}/materials', [TeacherClassroomController::class, 'uploadMaterial'])->name('classrooms.materials.upload');
        Route::delete('/classrooms/{classroom}/materials/{material}', [TeacherClassroomController::class, 'deleteMaterial'])->name('classrooms.materials.delete');
        Route::get('/classrooms/{classroom}/materials/{material}/download', [TeacherClassroomController::class, 'downloadMaterial'])->name('classrooms.materials.download');

        // Existing classroom test assignments and grading
        Route::delete('/classrooms/{classroom}/tests/{test}', [TeacherClassroomController::class, 'unassignTest'])->name('classrooms.tests.unassign');
        Route::get('/classrooms/{classroom}/tests/{test}/results', [TeacherClassroomController::class, 'testResults'])->name('classrooms.tests.results');
        Route::get('/classrooms/{classroom}/tests/{test}/marks', [TeacherClassroomController::class, 'marksList'])->name('classrooms.tests.marks');
        Route::get('/classrooms/{classroom}/tests/{test}/marks/export-csv', [TeacherClassroomController::class, 'exportMarksCsv'])->name('classrooms.tests.marks.export-csv');
        Route::get('/classrooms/{classroom}/tests/{test}/attempts/{attempt}/grade', [TeacherClassroomController::class, 'gradeAttempt'])->name('classrooms.tests.attempts.grade');
        Route::post('/classrooms/{classroom}/tests/{test}/attempts/{attempt}/grade', [TeacherClassroomController::class, 'saveGrades'])->name('classrooms.tests.attempts.save-grades');
        Route::post('/classrooms/{classroom}/tests/{test}/attempts/{attempt}/negative-marks', [TeacherClassroomController::class, 'updateNegativeMarks'])->name('classrooms.tests.attempts.negative-marks');
        Route::get('/classrooms/{classroom}/tests/{test}/evidence/{cheatingEvent}', [TeacherClassroomController::class, 'showEvidence'])->name('classrooms.tests.evidence.show');
    });

// Membership-based Classroom Enrollment & Participation (Accessible to any authenticated user under Option A)
Route::middleware(['auth', 'active.session'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        // Classrooms & Enrolled Examinations
        Route::get('/classrooms', [StudentClassroomController::class, 'index'])->name('classrooms.index');
        Route::post('/classrooms/join', [StudentClassroomController::class, 'join'])->name('classrooms.join');
        Route::get('/classrooms/{classroom}', [StudentClassroomController::class, 'show'])->name('classrooms.show');
        Route::get('/classrooms/{classroom}/materials/{material}/download', [StudentClassroomController::class, 'downloadMaterial'])->name('classrooms.materials.download');
        Route::get('/classrooms/{classroom}/tests/{test}', [StudentClassroomController::class, 'showTest'])->name('classrooms.tests.show');
        Route::get('/classrooms/{classroom}/tests/{test}/take', [StudentClassroomController::class, 'takeTest'])->name('classrooms.tests.take');
        Route::post('/classrooms/{classroom}/tests/{test}/save-answer', [StudentClassroomController::class, 'saveAnswer'])->name('classrooms.tests.save-answer');
        Route::post('/classrooms/{classroom}/tests/{test}/submit', [StudentClassroomController::class, 'submitTest'])->name('classrooms.tests.submit');
    });

// Shared Classroom Discussion Stream & Comments (Accessible to authenticated instructors and enrolled students)
Route::middleware(['auth', 'active.session'])
    ->prefix('classrooms/{classroom}')
    ->name('classrooms.')
    ->group(function () {
        // Posts & Announcements
        Route::post('/posts', [ClassroomPostController::class, 'store'])->name('posts.store');
        Route::put('/posts/{post}', [ClassroomPostController::class, 'update'])->name('posts.update');
        Route::delete('/posts/{post}', [ClassroomPostController::class, 'destroy'])->name('posts.destroy');

        // Comments & Threaded Replies
        Route::post('/posts/{post}/comments', [ClassroomCommentController::class, 'store'])->name('comments.store');
        Route::put('/comments/{comment}', [ClassroomCommentController::class, 'update'])->name('comments.update');
        Route::delete('/comments/{comment}', [ClassroomCommentController::class, 'destroy'])->name('comments.destroy');
    });

Route::middleware(['auth', 'active.session'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::get('/test', function () {
    return view('test');
})->middleware(['auth', 'active.session'])->name('test');

Route::post('/heartbeat', [AuthenticatedSessionController::class, 'heartbeat'])
    ->middleware('auth')
    ->name('heartbeat');

Route::post('/cheating-event', [CheatingEventController::class, 'store'])
    ->middleware('auth')
    ->name('cheating-event.store');
