<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Test;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TestController extends Controller
{
    /**
     * Display a listing of tests created by the teacher.
     */
    public function index(): View
    {
        $tests = Test::where('user_id', Auth::id())
            ->withCount('questions')
            ->withCount('attempts')
            ->latest()
            ->paginate(10);

        return view('teacher.tests.index', compact('tests'));
    }

    /**
     * Show the form for creating a new test.
     */
    public function create(): View
    {
        $classrooms = Classroom::where('user_id', Auth::id())
            ->orderBy('name')
            ->get();

        return view('teacher.tests.create', compact('classrooms'));
    }

    /**
     * Store a newly created test with both MCQ and Text questions, with optional classroom assignment.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
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

        // Verify teacher ownership of classroom if specified
        $assignedClassroom = null;
        if (!empty($request->classroom_id)) {
            $assignedClassroom = Classroom::where('id', $request->classroom_id)
                ->where('user_id', Auth::id())
                ->firstOrFail();
        }

        $test = DB::transaction(function () use ($request, $assignedClassroom) {
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

                // If MCQ, save all options and flag the chosen correct option
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

            // Assign to classroom immediately if selected
            if ($assignedClassroom) {
                $assignedClassroom->tests()->attach($test->id, [
                    'starts_at' => !empty($request->starts_at) ? Carbon::parse($request->starts_at) : null,
                    'due_at' => !empty($request->due_at) ? Carbon::parse($request->due_at) : null,
                    'allow_late_submissions' => (bool) $request->allow_late_submissions,
                    'is_active' => true,
                ]);
            }

            return $test;
        });

        $message = "Test '{$test->title}' created successfully with " . count($request->questions) . " question(s)!";
        if ($assignedClassroom) {
            $message .= " and assigned to '{$assignedClassroom->name}'.";
        }

        return redirect()->route('teacher.tests.show', $test)->with('success', $message);
    }

    /**
     * Display a specific test and all its questions.
     */
    public function show(Test $test): View
    {
        // Ensure teacher owns this test
        if ($test->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this test.');
        }

        $test->load(['questions.options', 'attempts.student', 'classrooms']);

        return view('teacher.tests.show', compact('test'));
    }
}
