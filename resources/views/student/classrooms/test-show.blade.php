<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('student.classrooms.show', $classroom) }}" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">
                        &larr; {{ $classroom->name }}
                    </a>
                </div>
                <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight mt-1">
                    {{ $test->title }}
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Examination Details & Proctoring Guidelines
                </p>
            </div>
            <a href="{{ route('student.classrooms.show', $classroom) }}" class="inline-flex items-center text-xs font-semibold px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg text-gray-700 dark:text-gray-200 transition">
                Back to Classroom
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Alerts -->
            @if (session('success'))
                <div class="p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl text-emerald-800 dark:text-emerald-300 text-sm flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('info'))
                <div class="p-4 bg-sky-50 dark:bg-sky-900/30 border border-sky-200 dark:border-sky-800 rounded-xl text-sky-800 dark:text-sky-300 text-sm flex items-center">
                    <svg class="w-5 h-5 mr-3 text-sky-600 dark:text-sky-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <span>{{ session('info') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 rounded-xl text-rose-800 dark:text-rose-300 text-sm flex items-center">
                    <svg class="w-5 h-5 mr-3 text-rose-600 dark:text-rose-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- Main Test Overview Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-8 border border-gray-100 dark:border-gray-700 space-y-6">

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-gray-100 dark:border-gray-700">
                    <div>
                        <span class="inline-block px-2.5 py-0.5 rounded bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 text-xs font-semibold uppercase tracking-wider mb-2">
                            {{ $classroom->name }}
                        </span>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $test->title }}</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Instructor: <strong>{{ $test->teacher->name }}</strong>
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="px-4 py-2 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl text-center">
                            <span class="block text-2xl font-black text-emerald-700 dark:text-emerald-300">{{ $test->total_marks }}</span>
                            <span class="text-[11px] text-gray-500 dark:text-gray-400 uppercase font-bold">Total Marks</span>
                        </div>
                        <div class="px-4 py-2 bg-gray-50 dark:bg-gray-700/30 rounded-xl text-center">
                            <span class="block text-2xl font-black text-gray-800 dark:text-gray-200">{{ $test->duration_minutes }}m</span>
                            <span class="text-[11px] text-gray-500 dark:text-gray-400 uppercase font-bold">Duration</span>
                        </div>
                    </div>
                </div>

                @if ($test->description)
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Description</h4>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            {{ $test->description }}
                        </p>
                    </div>
                @endif

                <!-- Question Breakdown & Timing -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Structure</h4>
                        <ul class="text-xs text-gray-700 dark:text-gray-300 space-y-1">
                            <li>Total Questions: <strong>{{ $test->questions->count() }}</strong></li>
                            <li>Multiple Choice (MCQ): <strong>{{ $test->mcqCount() }}</strong></li>
                            <li>Written / Text Answers: <strong>{{ $test->textCount() }}</strong></li>
                        </ul>
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Availability Schedule</h4>
                        <ul class="text-xs text-gray-700 dark:text-gray-300 space-y-1">
                            <li>Opens: <strong>{{ $assignment->starts_at ? $assignment->starts_at->format('M d, Y h:i A') : 'Immediately' }}</strong></li>
                            <li>Due By: <strong>{{ $assignment->due_at ? $assignment->due_at->format('M d, Y h:i A') : 'No Closing Deadline' }}</strong></li>
                            @if ($assignment->due_at)
                                <li>Late Submissions: <strong class="{{ $assignment->isLateAllowed() ? 'text-amber-600 dark:text-amber-400' : 'text-gray-500' }}">{{ $assignment->isLateAllowed() ? 'Allowed (flagged)' : 'Not permitted' }}</strong></li>
                            @endif
                            <li>Status: <strong class="uppercase font-bold {{ $assignment->isAvailable() ? 'text-emerald-600' : ($assignment->isLateAllowed() && $assignment->isPastDeadline() ? 'text-amber-600' : 'text-rose-600') }}">{{ $assignment->status() }}</strong></li>
                        </ul>
                    </div>
                </div>

                <!-- Anti-cheating Proctoring Rules -->
                <div class="p-5 bg-amber-50/80 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl text-amber-900 dark:text-amber-200 space-y-2 text-xs">
                    <div class="flex items-center gap-2 font-bold text-sm">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <span>Mandatory Anti-Cheating & Proctoring Regulations</span>
                    </div>
                    <ul class="list-disc list-inside space-y-1 text-amber-800 dark:text-amber-300 pl-1">
                        <li><strong>Webcam Feed:</strong> Live video monitoring with face and gaze detection is active during the test.</li>
                        <li><strong>Fullscreen Enforcement:</strong> You must enter fullscreen mode to begin. Exiting fullscreen logs suspicious events.</li>
                        <li><strong>Tab & Focus Tracking:</strong> Switching tabs or applications will trigger suspicion scoring and notify instructors.</li>
                        <li><strong>Timed Attempt:</strong> When you start, your exam timer will begin countdown.</li>
                    </ul>
                </div>

                <!-- Late Submission Warning if Applicable -->
                @if ($assignment->isPastDeadline() && $assignment->isLateAllowed() && (!$attempt || !$attempt->isSubmitted()))
                    <div class="p-4 bg-amber-50 dark:bg-amber-900/30 border border-amber-300 dark:border-amber-700 rounded-xl text-xs text-amber-900 dark:text-amber-200 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span>The submission deadline for this exam has passed. Late submissions are permitted, but your attempt will be flagged as <strong>Submitted Late</strong>.</span>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-200 dark:bg-amber-800 text-amber-900 dark:text-amber-100">
                            Late Mode
                        </span>
                    </div>
                @endif

                <!-- Action Button Area -->
                <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        @if ($attempt && $attempt->isSubmitted())
                            <div class="text-xs text-gray-500">
                                <span>Exam submitted on <strong>{{ $attempt->submitted_at ? $attempt->submitted_at->format('M d, Y h:i A') : 'Completed' }}</strong></span>
                                @if ($attempt->is_late)
                                    <span class="ml-2 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border border-amber-300">
                                        Submitted Late
                                    </span>
                                @else
                                    <span class="ml-2 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                        On Time
                                    </span>
                                @endif

                                @if ($attempt->isFullyGraded())
                                    <span class="ml-2 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                        Fully Graded
                                    </span>
                                @elseif ($attempt->isPartiallyGraded())
                                    <span class="ml-2 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                                        Partially Graded
                                    </span>
                                @else
                                    <span class="ml-2 px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300">
                                        Grading in Progress
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div>
                        @if ($attempt && $attempt->isSubmitted())
                            <div class="flex flex-col sm:items-end gap-2">
                                @if ($attempt->isFullyGraded())
                                    <span class="px-4 py-2 bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-200 rounded-xl font-bold text-sm">
                                        Final Score: {{ $attempt->final_marks }} / {{ $attempt->total_marks }} Marks
                                    </span>
                                @elseif ($attempt->isPartiallyGraded())
                                    <span class="px-4 py-2 bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-200 rounded-xl font-bold text-sm">
                                        Current Verified: {{ $attempt->score }} / {{ $attempt->total_marks }} Marks (Grading in Progress)
                                    </span>
                                @else
                                    <span class="px-4 py-2 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 rounded-xl font-bold text-xs border border-indigo-200 dark:border-indigo-800">
                                        Written Answers Awaiting Instructor Review
                                    </span>
                                @endif
                            </div>
                        @elseif ($assignment->isUpcoming())
                            <span class="inline-flex items-center px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-xl text-sm font-semibold cursor-not-allowed">
                                Exam opens {{ $assignment->starts_at->format('M d, Y h:i A') }}
                            </span>
                        @elseif ($assignment->isPastDeadline() && !$assignment->isLateAllowed())
                            <span class="inline-flex items-center px-4 py-2.5 bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300 rounded-xl text-sm font-semibold cursor-not-allowed">
                                Deadline Passed &bull; Submissions Closed
                            </span>
                        @else
                            <a
                                href="{{ route('student.classrooms.tests.take', [$classroom, $test]) }}"
                                class="inline-flex items-center px-6 py-3 {{ $assignment->isPastDeadline() ? 'bg-amber-600 hover:bg-amber-700' : 'bg-emerald-600 hover:bg-emerald-700' }} text-white rounded-xl font-bold text-base shadow-md hover:shadow-lg transition transform hover:-translate-y-0.5"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                @if ($attempt && $attempt->status === \App\Models\ExamAttempt::STATUS_IN_PROGRESS)
                                    Resume Proctored Exam
                                @elseif ($assignment->isPastDeadline())
                                    Launch Exam (Late Submission)
                                @else
                                    Launch Proctored Exam
                                @endif
                            </a>
                        @endif
                    </div>
                </div>

                @if ($attempt && $attempt->isFullyGraded() && !empty($attempt->teacher_feedback))
                    <div class="p-5 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">Instructor Evaluation Feedback</h4>
                        </div>
                        <p class="text-sm text-gray-800 dark:text-gray-200 leading-relaxed whitespace-pre-wrap pl-4">
                            {{ $attempt->teacher_feedback }}
                        </p>
                    </div>
                @endif

            </div>

        </div>
    </div>
</x-app-layout>
