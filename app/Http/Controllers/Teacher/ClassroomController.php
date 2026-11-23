<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\CheatingEvent;
use App\Models\Classroom;
use App\Models\ClassroomTest;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Models\Test;
use App\Models\ClassroomJoinRequest;
use App\Models\ClassroomMaterial;
use App\Models\MarkDeductionAudit;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClassroomController extends Controller
{
    /**
     * Display a listing of classrooms created by this teacher.
     */
    public function index(): View
    {
        $classrooms = Classroom::where('user_id', Auth::id())
            ->withCount(['students', 'tests'])
            ->latest()
            ->paginate(12);

        return view('teacher.classrooms.index', compact('classrooms'));
    }

    /**
     * Show the form for creating a new classroom.
     */
    public function create(): View
    {
        return view('teacher.classrooms.create');
    }

    /**
     * Store a newly created classroom with a unique join code.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'banner_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:3072'],
            'is_email_restricted' => ['nullable', 'boolean'],
            'allowed_email_domain' => ['nullable', 'required_if:is_email_restricted,1', 'string', 'max:255'],
        ]);

        $isRestricted = $request->boolean('is_email_restricted');
        $allowedDomain = $isRestricted && !empty($validated['allowed_email_domain'])
            ? trim($validated['allowed_email_domain'])
            : null;

        $bannerPath = null;
        if ($request->hasFile('banner_image') && $request->file('banner_image')->isValid()) {
            $bannerPath = $request->file('banner_image')->store('classroom_banners', 'public');
        } elseif ($request->filled('default_banner') && in_array($request->input('default_banner'), Classroom::DEFAULT_BANNERS)) {
            $bannerPath = $request->input('default_banner');
        } else {
            // Automatically assign a random academic banner from the predefined collection
            $bannerPath = Classroom::getRandomDefaultBanner();
        }

        $classroom = Classroom::create([
            'user_id' => Auth::id(),
            'name' => trim($validated['name']),
            'description' => !empty($validated['description']) ? trim($validated['description']) : null,
            'banner_image_path' => $bannerPath,
            'code' => Classroom::generateUniqueCode(),
            'is_email_restricted' => $isRestricted && !empty($allowedDomain),
            'allowed_email_domain' => $allowedDomain,
        ]);

        return redirect()->route('teacher.classrooms.show', $classroom)
            ->with('success', "Classroom '{$classroom->name}' created successfully with Join Code: {$classroom->code}");
    }

    /**
     * Show the form for editing the specified classroom.
     */
    public function edit(Classroom $classroom): View
    {
        // Enforce ownership: Teachers can only edit their own classrooms
        if ($classroom->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to edit this classroom.');
        }

        return view('teacher.classrooms.edit', compact('classroom'));
    }

    /**
     * Update the specified classroom in storage.
     */
    public function update(Request $request, Classroom $classroom): RedirectResponse
    {
        // Enforce ownership: Teachers can only edit their own classrooms
        if ($classroom->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to update this classroom.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'banner_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:3072'],
            'remove_banner' => ['nullable', 'boolean'],
            'is_email_restricted' => ['nullable', 'boolean'],
            'allowed_email_domain' => ['nullable', 'required_if:is_email_restricted,1', 'string', 'max:255'],
        ]);

        $isRestricted = $request->boolean('is_email_restricted');
        $allowedDomain = $isRestricted && !empty($validated['allowed_email_domain'])
            ? trim($validated['allowed_email_domain'])
            : null;

        $bannerPath = $classroom->banner_image_path;
        if ($request->boolean('remove_banner')) {
            if ($bannerPath && !in_array($bannerPath, Classroom::DEFAULT_BANNERS) && Storage::disk('public')->exists($bannerPath)) {
                Storage::disk('public')->delete($bannerPath);
            }
            $bannerPath = null;
        } elseif ($request->hasFile('banner_image') && $request->file('banner_image')->isValid()) {
            if ($bannerPath && !in_array($bannerPath, Classroom::DEFAULT_BANNERS) && Storage::disk('public')->exists($bannerPath)) {
                Storage::disk('public')->delete($bannerPath);
            }
            $bannerPath = $request->file('banner_image')->store('classroom_banners', 'public');
        } elseif ($request->filled('default_banner') && in_array($request->input('default_banner'), Classroom::DEFAULT_BANNERS)) {
            if ($bannerPath && !in_array($bannerPath, Classroom::DEFAULT_BANNERS) && Storage::disk('public')->exists($bannerPath)) {
                Storage::disk('public')->delete($bannerPath);
            }
            $bannerPath = $request->input('default_banner');
        }
        // If no new banner or default was selected, $bannerPath retains $classroom->banner_image_path intact!

        $classroom->update([
            'name' => trim($validated['name']),
            'description' => !empty($validated['description']) ? trim($validated['description']) : null,
            'banner_image_path' => $bannerPath,
            'is_email_restricted' => $isRestricted && !empty($allowedDomain),
            'allowed_email_domain' => $allowedDomain,
        ]);

        return redirect()->route('teacher.classrooms.show', $classroom)
            ->with('success', "Classroom '{$classroom->name}' updated successfully.");
    }

    /**
     * Display the specified classroom, enrolled students, assigned tests, and assignment form.
     */
    public function show(Classroom $classroom): View
    {
        // Enforce ownership: Teachers can only view and manage their own classrooms
        if ($classroom->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this classroom.');
        }

        $classroom->load([
            'students' => fn ($query) => $query->orderBy('name'),
            'tests.questions',
            'classroomTests.test.questions',
            'joinRequests' => fn ($query) => $query->where('status', ClassroomJoinRequest::STATUS_PENDING)->with('user')->latest(),
            'materials.uploader',
            'posts' => fn ($query) => $query->with([
                'user',
                'comments' => fn ($q) => $q->whereNull('parent_id')->with(['user', 'replies.user'])->oldest(),
            ])->latest(),
        ]);

        // Calculate submissions count for each assigned test in this classroom
        $submissionCounts = ExamAttempt::where('classroom_id', $classroom->id)
            ->selectRaw('test_id, count(*) as count')
            ->groupBy('test_id')
            ->pluck('count', 'test_id');

        return view('teacher.classrooms.show', compact('classroom', 'submissionCounts'));
    }

    /**
     * Remove / unassign a test from this classroom.
     */
    public function unassignTest(Classroom $classroom, Test $test): RedirectResponse
    {
        if ($classroom->user_id !== Auth::id() || $test->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $classroom->tests()->detach($test->id);

        return redirect()->route('teacher.classrooms.show', $classroom)
            ->with('success', "Test '{$test->title}' has been removed from this classroom.");
    }

    /**
     * View all student results and submissions for an assigned test in this classroom.
     */
    public function testResults(Classroom $classroom, Test $test): View
    {
        if ($classroom->user_id !== Auth::id() || $test->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to these results.');
        }

        if (!$classroom->hasTest($test->id)) {
            abort(404, 'Test is not assigned to this classroom.');
        }

        $assignment = ClassroomTest::where('classroom_id', $classroom->id)
            ->where('test_id', $test->id)
            ->firstOrFail();

        $attempts = ExamAttempt::where('classroom_id', $classroom->id)
            ->where('test_id', $test->id)
            ->with(['student', 'test.questions', 'answers.question', 'answers.selectedOption', 'cheatingEvents'])
            ->latest()
            ->get();

        $submittedOnTime = $attempts->filter(fn ($a) => $a->isSubmitted() && !$a->is_late);
        $submittedLate = $attempts->filter(fn ($a) => $a->isSubmitted() && (bool) $a->is_late);
        $inProgress = $attempts->filter(fn ($a) => $a->isInProgress());

        $attemptedStudentIds = $attempts->pluck('user_id')->all();

        $unattemptedStudents = $classroom->students()
            ->whereNotIn('users.id', $attemptedStudentIds)
            ->orderBy('name')
            ->get();

        return view('teacher.classrooms.results', compact(
            'classroom',
            'test',
            'assignment',
            'attempts',
            'submittedOnTime',
            'submittedLate',
            'inProgress',
            'unattemptedStudents'
        ));
    }

    /**
     * Show the grading and anti-cheating review page for an individual student attempt.
     */
    public function gradeAttempt(Classroom $classroom, Test $test, ExamAttempt $attempt): View
    {
        if ($classroom->user_id !== Auth::id() || $test->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        if ($attempt->classroom_id !== $classroom->id || $attempt->test_id !== $test->id) {
            abort(404, 'Attempt does not belong to this classroom test.');
        }

        $attempt->load([
            'student',
            'answers.question.options',
            'answers.selectedOption',
        ]);

        $test->load('questions.options');

        $cheatingEvents = $attempt->associatedCheatingEvents();

        return view('teacher.classrooms.grade', compact('classroom', 'test', 'attempt', 'cheatingEvents'));
    }

    /**
     * Save faculty grading and feedback for written answers in an exam attempt.
     */
    public function saveGrades(Request $request, Classroom $classroom, Test $test, ExamAttempt $attempt): RedirectResponse
    {
        if ($classroom->user_id !== Auth::id() || $test->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        if ($attempt->classroom_id !== $classroom->id || $attempt->test_id !== $test->id) {
            abort(404, 'Attempt does not belong to this classroom test.');
        }

        $writtenQuestions = $test->questions()
            ->where('question_type', Question::TYPE_TEXT)
            ->get()
            ->keyBy('id');

        $rules = [
            'marks' => ['nullable', 'array'],
            'feedback' => ['nullable', 'array'],
            'feedback.*' => ['nullable', 'string', 'max:2000'],
            'overall_feedback' => ['nullable', 'string', 'max:5000'],
        ];

        foreach ($writtenQuestions as $qId => $question) {
            $rules["marks.{$qId}"] = ['nullable', 'numeric', 'min:0', "max:{$question->marks}"];
        }

        $request->validate($rules);

        $marksInput = $request->input('marks', []);
        $feedbackInput = $request->input('feedback', []);

        DB::transaction(function () use ($attempt, $writtenQuestions, $marksInput, $feedbackInput, $request) {
            foreach ($writtenQuestions as $qId => $question) {
                // If marks were supplied for this question
                if (array_key_exists($qId, $marksInput) && $marksInput[$qId] !== null && $marksInput[$qId] !== '') {
                    $mark = (float) $marksInput[$qId];
                    $feedback = $feedbackInput[$qId] ?? null;

                    StudentAnswer::updateOrCreate(
                        [
                            'exam_attempt_id' => $attempt->id,
                            'question_id' => $qId,
                        ],
                        [
                            'marks_awarded' => $mark,
                            'teacher_feedback' => !empty($feedback) ? trim($feedback) : null,
                            'is_graded' => true,
                        ]
                    );
                }
            }

            // Recalculate total score = sum of MCQ marks + faculty written marks
            $totalScore = (float) $attempt->answers()->sum('marks_awarded');

            $overallFeedback = $request->input('overall_feedback');
            $attempt->score = $totalScore;
            if ($request->has('overall_feedback')) {
                $attempt->teacher_feedback = !empty($overallFeedback) ? trim($overallFeedback) : null;
            }

            // Check if all written questions are now graded
            if ($attempt->isFullyGraded()) {
                $attempt->status = ExamAttempt::STATUS_GRADED;
                if (!$attempt->graded_at) {
                    $attempt->graded_at = now();
                }
            }

            $attempt->save();
        });

        $targetTab = $attempt->is_late ? 'late' : 'on_time';

        return redirect()->route('teacher.classrooms.tests.results', [$classroom, $test, 'tab' => $targetTab])
            ->with('success', "Grades and feedback saved successfully for {$attempt->student->name}.");
    }

    /**
     * Securely stream an evidence screenshot to authorized teachers only.
     */
    public function showEvidence(Classroom $classroom, Test $test, CheatingEvent $cheatingEvent)
    {
        if ($classroom->user_id !== Auth::id() || $test->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to evidence.');
        }

        if (!$classroom->hasTest($test->id)) {
            abort(404, 'Test is not assigned to this classroom.');
        }

        $isAuthorizedEvent = false;

        if ($cheatingEvent->exam_attempt_id) {
            $attempt = ExamAttempt::find($cheatingEvent->exam_attempt_id);
            if ($attempt && $attempt->classroom_id === $classroom->id && $attempt->test_id === $test->id) {
                $isAuthorizedEvent = true;
            }
        } else {
            // Legacy event verification
            $studentInClassroom = $classroom->hasStudent($cheatingEvent->user_id);
            if ($studentInClassroom && $cheatingEvent->occurred_at) {
                $attempt = ExamAttempt::where('classroom_id', $classroom->id)
                    ->where('test_id', $test->id)
                    ->where('user_id', $cheatingEvent->user_id)
                    ->where('started_at', '<=', $cheatingEvent->occurred_at)
                    ->where(function ($q) use ($cheatingEvent) {
                        $q->whereNull('submitted_at')
                            ->orWhere('submitted_at', '>=', $cheatingEvent->occurred_at);
                    })
                    ->first();

                if ($attempt) {
                    $isAuthorizedEvent = true;
                }
            }
        }

        if (!$isAuthorizedEvent) {
            abort(403, 'Evidence does not belong to an authorized attempt in this classroom.');
        }

        if (!$cheatingEvent->evidence_path || !Storage::disk('public')->exists($cheatingEvent->evidence_path)) {
            abort(404, 'Evidence screenshot file not found.');
        }

        return Storage::disk('public')->response($cheatingEvent->evidence_path);
    }

    /**
     * Show the student marks list for an assigned test after the deadline has passed.
     */
    public function marksList(Classroom $classroom, Test $test): View
    {
        // 1. Teacher Authorization
        if ($classroom->user_id !== Auth::id() || $test->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to marks list.');
        }

        if (!$classroom->hasTest($test->id)) {
            abort(404, 'Test is not assigned to this classroom.');
        }

        $assignment = ClassroomTest::where('classroom_id', $classroom->id)
            ->where('test_id', $test->id)
            ->firstOrFail();

        // 2. Server-side Deadline Enforcement: ONLY accessible after the test deadline
        if (!$assignment->isPastDeadline()) {
            abort(403, 'The marks list is only accessible after the test deadline has passed.');
        }

        // 3. Retrieve student attempts and all enrolled students
        $attempts = ExamAttempt::where('classroom_id', $classroom->id)
            ->where('test_id', $test->id)
            ->with(['student', 'markDeductionAudits.teacher'])
            ->get()
            ->keyBy('user_id');

        $students = $classroom->students()->orderBy('name')->get();

        // Check if an actual index or roll number column exists in the database
        $hasRollField = Schema::hasColumn('users', 'roll_number')
            || Schema::hasColumn('users', 'index_number')
            || Schema::hasColumn('users', 'student_id');

        // Compile marks rows
        $marksData = $students->map(function ($student) use ($attempts, $hasRollField) {
            $attempt = $attempts->get($student->id);

            // Never invent a college roll number from a database ID
            $rollNumber = null;
            if ($hasRollField) {
                $rollNumber = $student->roll_number ?? $student->index_number ?? $student->student_id ?? null;
            }

            $originalMarks = $attempt ? (float) $attempt->score : 0.0;
            $negativeMarks = $attempt ? (float) ($attempt->negative_marks ?? 0.0) : 0.0;
            $finalMarks = $attempt ? (float) $attempt->final_marks : 0.0;

            return [
                'student' => $student,
                'attempt' => $attempt,
                'roll_number' => $rollNumber,
                'has_attempt' => (bool) $attempt,
                'status' => $attempt ? $attempt->status : 'not_started',
                'is_late' => $attempt ? (bool) $attempt->is_late : false,
                'original_marks' => $originalMarks,
                'negative_marks' => $negativeMarks,
                'negative_marks_reason' => $attempt ? $attempt->negative_marks_reason : null,
                'final_marks' => $finalMarks,
            ];
        });

        // Summary statistics
        $attemptCount = $attempts->count();
        $submittedAttempts = $attempts->filter(fn ($a) => $a->isSubmitted());
        $submittedCount = $submittedAttempts->count();
        $averageFinalMarks = $submittedCount > 0
            ? round($submittedAttempts->avg(fn ($a) => $a->final_marks), 2)
            : 0.0;
        $highestFinalMarks = $submittedCount > 0
            ? $submittedAttempts->max(fn ($a) => $a->final_marks)
            : 0.0;

        return view('teacher.classrooms.marks', compact(
            'classroom',
            'test',
            'assignment',
            'marksData',
            'hasRollField',
            'attemptCount',
            'submittedCount',
            'averageFinalMarks',
            'highestFinalMarks'
        ));
    }

    /**
     * Apply or update teacher-controlled negative marks with audit logging.
     */
    public function updateNegativeMarks(Request $request, Classroom $classroom, Test $test, ExamAttempt $attempt): RedirectResponse
    {
        if ($classroom->user_id !== Auth::id() || $test->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        if ($attempt->classroom_id !== $classroom->id || $attempt->test_id !== $test->id) {
            abort(404, 'Attempt does not belong to this classroom test.');
        }

        $originalScore = (float) $attempt->score;

        $request->validate([
            'negative_marks' => ['required', 'numeric', 'min:0', "max:{$originalScore}"],
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'negative_marks.required' => 'Please provide the negative marks to deduct.',
            'negative_marks.numeric' => 'Negative marks must be a valid numeric value.',
            'negative_marks.min' => 'Negative marks cannot be less than 0.',
            'negative_marks.max' => "Negative marks cannot exceed the student's original exam score of {$originalScore}.",
            'reason.required' => 'A reason or audit explanation is required when adjusting marks.',
        ]);

        $negative = round((float) $request->input('negative_marks'), 2);
        $reason = trim($request->input('reason'));

        DB::transaction(function () use ($attempt, $originalScore, $negative, $reason) {
            $attempt->negative_marks = $negative;
            $attempt->negative_marks_reason = $reason;
            $attempt->save();

            // Store audit record
            $attempt->markDeductionAudits()->create([
                'user_id' => Auth::id(),
                'original_score' => $originalScore,
                'negative_marks' => $negative,
                'final_marks' => $attempt->final_marks,
                'reason' => $reason,
            ]);
        });

        return back()->with('success', "Negative marks updated for {$attempt->student->name}. Final Marks: {$attempt->final_marks} / {$attempt->total_marks}.");
    }

    /**
     * Download an Excel-compatible CSV marks report after the test deadline.
     */
    public function exportMarksCsv(Classroom $classroom, Test $test): StreamedResponse
    {
        // 1. Teacher Authorization
        if ($classroom->user_id !== Auth::id() || $test->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to export marks.');
        }

        if (!$classroom->hasTest($test->id)) {
            abort(404, 'Test is not assigned to this classroom.');
        }

        $assignment = ClassroomTest::where('classroom_id', $classroom->id)
            ->where('test_id', $test->id)
            ->firstOrFail();

        // 2. Server-side Deadline Enforcement
        if (!$assignment->isPastDeadline()) {
            abort(403, 'The marks report export is only available after the test deadline has passed.');
        }

        // Check if actual roll/index column exists in users table
        $hasRollField = Schema::hasColumn('users', 'roll_number')
            || Schema::hasColumn('users', 'index_number')
            || Schema::hasColumn('users', 'student_id');

        $attempts = ExamAttempt::where('classroom_id', $classroom->id)
            ->where('test_id', $test->id)
            ->with('student')
            ->get()
            ->keyBy('user_id');

        $students = $classroom->students()->orderBy('name')->get();

        // Filename including classroom name and test/assignment title
        $cleanClassroom = Str::slug($classroom->name, '_');
        $cleanTest = Str::slug($test->title, '_');
        $filename = "{$cleanClassroom}_{$cleanTest}_marks_report.csv";

        return response()->streamDownload(function () use ($classroom, $test, $assignment, $students, $attempts, $hasRollField) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM so Microsoft Excel correctly recognizes character encoding
            fwrite($handle, "\xEF\xBB\xBF");

            // Report Header
            fputcsv($handle, ['Classroom', $classroom->name]);
            fputcsv($handle, ['Test Title', $test->title]);
            fputcsv($handle, ['Deadline / Due Date', $assignment->due_at ? $assignment->due_at->format('Y-m-d H:i:s') : 'None']);
            fputcsv($handle, ['Total Test Marks', (string) $test->total_marks]);
            fputcsv($handle, ['Report Generated', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, []); // Blank separator row

            // Table Columns: (Roll Number if actual field exists), Student Name, Final Marks
            // "Do not include a separate Negative Marks column."
            if ($hasRollField) {
                fputcsv($handle, ['Student Index / Roll Number', 'Student Name', 'Final Marks']);
            } else {
                fputcsv($handle, ['Student Name', 'Final Marks']);
            }

            // Student Rows
            foreach ($students as $student) {
                $attempt = $attempts->get($student->id);
                $finalMarks = $attempt ? number_format($attempt->final_marks, 2, '.', '') : '0.00';

                if ($hasRollField) {
                    $rollNumber = $student->roll_number ?? $student->index_number ?? $student->student_id ?? 'N/A';
                    fputcsv($handle, [
                        $rollNumber,
                        $student->name,
                        $finalMarks,
                    ]);
                } else {
                    fputcsv($handle, [
                        $student->name,
                        $finalMarks,
                    ]);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Review and update the status of an access request (Accept, Reject, Pending).
     */
    public function updateJoinRequestStatus(Request $request, Classroom $classroom, ClassroomJoinRequest $joinRequest): RedirectResponse
    {
        // Enforce ownership: Only the classroom teacher or authorized admin can review
        if ($classroom->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to manage join requests for this classroom.');
        }

        if ($joinRequest->classroom_id !== $classroom->id) {
            abort(404, 'Join request does not belong to this classroom.');
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:accepted,rejected,pending'],
        ]);

        $newStatus = $validated['status'];
        $studentUser = $joinRequest->user;

        if ($newStatus === ClassroomJoinRequest::STATUS_ACCEPTED) {
            $joinRequest->update([
                'status' => ClassroomJoinRequest::STATUS_ACCEPTED,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            // Create normal classroom membership using existing membership system
            $classroom->students()->syncWithoutDetaching([$joinRequest->user_id]);

            return back()->with('success', "Access request for {$studentUser->name} ({$studentUser->email}) accepted. Student enrolled successfully.");
        } elseif ($newStatus === ClassroomJoinRequest::STATUS_REJECTED) {
            $joinRequest->update([
                'status' => ClassroomJoinRequest::STATUS_REJECTED,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            // Ensure access is completely revoked: detach from classroom memberships
            $classroom->students()->detach($joinRequest->user_id);

            return back()->with('success', "Access request for {$studentUser->name} ({$studentUser->email}) rejected.");
        } else {
            // Pending: leave request undecided
            $joinRequest->update([
                'status' => ClassroomJoinRequest::STATUS_PENDING,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);

            // Ensure pending request does not grant classroom access
            $classroom->students()->detach($joinRequest->user_id);

            return back()->with('success', "Access request for {$studentUser->name} ({$studentUser->email}) marked as pending review.");
        }
    }

    /**
     * Remove an enrolled student from the classroom.
     */
    public function removeStudent(Request $request, Classroom $classroom, User $student): RedirectResponse
    {
        // Enforce ownership: Teachers can only manage their own classrooms
        if ($classroom->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to manage students in this classroom.');
        }

        // Verify the student is actually enrolled in this classroom
        if (!$classroom->hasStudent($student->id)) {
            return back()->with('error', "Student {$student->name} is not enrolled in this classroom.");
        }

        // Detach membership from this classroom
        // This does NOT delete the user account, exam attempts, answers, evidence, or records in other classrooms.
        // It also does NOT delete historical classroom_join_requests.
        $classroom->students()->detach($student->id);

        return back()->with('success', "Student {$student->name} has been removed from this classroom.");
    }

    /**
     * Show form for creating a new test scoped directly to this classroom.
     */
    public function createTest(Classroom $classroom): View
    {
        if ($classroom->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to create tests in this classroom.');
        }

        return view('teacher.tests.create', [
            'classroom' => $classroom,
            'classrooms' => Classroom::where('user_id', Auth::id())->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a new test and attach it directly to this classroom.
     */
    public function storeTest(Request $request, Classroom $classroom): RedirectResponse
    {
        if ($classroom->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to create tests in this classroom.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'starts_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'allow_late_submissions' => ['nullable', 'boolean'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.text' => ['required', 'string', 'max:5000'],
            'questions.*.type' => ['required', 'string', 'in:mcq,text'],
            'questions.*.marks' => ['required', 'integer', 'min:1', 'max:100'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*.text' => ['nullable', 'string', 'max:1000'],
            'questions.*.correct_option' => ['nullable'],
        ]);

        // Deep validation for MCQs
        foreach ($request->questions as $index => $q) {
            $qNum = $index + 1;
            if ($q['type'] === Question::TYPE_MCQ) {
                if (empty($q['options']) || count($q['options']) < 2) {
                    throw ValidationException::withMessages([
                        "questions.{$index}.options" => "Question #{$qNum} (MCQ) must have at least 2 options.",
                    ]);
                }

                foreach ($q['options'] as $optIdx => $opt) {
                    if (!isset($opt['text']) || trim((string) $opt['text']) === '') {
                        throw ValidationException::withMessages([
                            "questions.{$index}.options.{$optIdx}.text" => "Option " . chr(65 + $optIdx) . " for Question #{$qNum} cannot be blank.",
                        ]);
                    }
                }

                if (!isset($q['correct_option']) || !is_numeric($q['correct_option'])) {
                    throw ValidationException::withMessages([
                        "questions.{$index}.correct_option" => "Please select the correct option for Question #{$qNum}.",
                    ]);
                }

                $correctIdx = (int) $q['correct_option'];
                if (!isset($q['options'][$correctIdx])) {
                    throw ValidationException::withMessages([
                        "questions.{$index}.correct_option" => "Invalid correct option selection for Question #{$qNum}.",
                    ]);
                }
            }
        }

        $test = DB::transaction(function () use ($request, $classroom) {
            $totalMarks = 0;
            foreach ($request->questions as $q) {
                $totalMarks += (int) $q['marks'];
            }

            $test = Test::create([
                'user_id' => Auth::id(),
                'title' => trim($request->title),
                'description' => $request->description ? trim($request->description) : null,
                'duration_minutes' => (int) $request->duration_minutes,
                'total_marks' => $totalMarks,
                'is_published' => true,
            ]);

            foreach ($request->questions as $qIndex => $q) {
                $question = Question::create([
                    'test_id' => $test->id,
                    'question_type' => $q['type'],
                    'question_text' => trim($q['text']),
                    'marks' => (int) $q['marks'],
                    'order' => $qIndex + 1,
                ]);

                if ($q['type'] === Question::TYPE_MCQ) {
                    $selectedCorrect = (int) $q['correct_option'];
                    foreach ($q['options'] as $optIndex => $opt) {
                        QuestionOption::create([
                            'question_id' => $question->id,
                            'option_text' => trim($opt['text']),
                            'is_correct' => ($optIndex === $selectedCorrect),
                            'order' => $optIndex + 1,
                        ]);
                    }
                }
            }

            $classroom->tests()->attach($test->id, [
                'starts_at' => !empty($request->starts_at) ? Carbon::parse($request->starts_at) : null,
                'due_at' => !empty($request->due_at) ? Carbon::parse($request->due_at) : null,
                'allow_late_submissions' => (bool) $request->allow_late_submissions,
                'is_active' => true,
            ]);

            return $test;
        });

        return redirect()->route('teacher.classrooms.show', $classroom)
            ->with('success', "Test '{$test->title}' created and assigned to '{$classroom->name}' successfully!");
    }

    /**
     * Upload a study material file to this classroom.
     */
    public function uploadMaterial(Request $request, Classroom $classroom): RedirectResponse
    {
        if ($classroom->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to upload materials to this classroom.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'file' => [
                'required',
                'file',
                'max:20480', // 20MB max
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,jpg,jpeg,png,webp,gif',
            ],
        ]);

        $uploadedFile = $request->file('file');
        $originalFilename = $uploadedFile->getClientOriginalName();
        $mimeType = $uploadedFile->getClientMimeType() ?: 'application/octet-stream';
        $fileSize = $uploadedFile->getSize();

        $storedPath = $uploadedFile->store("materials/{$classroom->id}", 'local');

        ClassroomMaterial::create([
            'classroom_id' => $classroom->id,
            'user_id' => Auth::id(),
            'title' => trim($validated['title']),
            'description' => !empty($validated['description']) ? trim($validated['description']) : null,
            'file_path' => $storedPath,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
        ]);

        return redirect()->route('teacher.classrooms.show', $classroom)
            ->with('success', "Material '{$validated['title']}' uploaded successfully.");
    }

    /**
     * Delete a study material file from this classroom.
     */
    public function deleteMaterial(Classroom $classroom, ClassroomMaterial $material): RedirectResponse
    {
        if ($classroom->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to delete materials from this classroom.');
        }

        if ($material->classroom_id !== $classroom->id) {
            abort(404, 'Material does not belong to this classroom.');
        }

        if (Storage::disk('local')->exists($material->file_path)) {
            Storage::disk('local')->delete($material->file_path);
        }

        $materialTitle = $material->title;
        $material->delete();

        return redirect()->route('teacher.classrooms.show', $classroom)
            ->with('success', "Material '{$materialTitle}' deleted successfully.");
    }

    /**
     * Download a study material file (teacher).
     */
    public function downloadMaterial(Classroom $classroom, ClassroomMaterial $material): StreamedResponse
    {
        if ($classroom->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to download this material.');
        }

        if ($material->classroom_id !== $classroom->id) {
            abort(404, 'Material does not belong to this classroom.');
        }

        if (!Storage::disk('local')->exists($material->file_path)) {
            abort(404, 'File not found on storage.');
        }

        return Storage::disk('local')->download($material->file_path, $material->original_filename);
    }
}

