@php
    $test = $assignment->test;
    $classroom = $assignment->classroom;
    $isAvailable = $assignment->isAvailable();
    $isUpcoming = $assignment->isUpcoming();
    $isPastDeadline = $assignment->isPastDeadline();
    $isLateAllowed = $assignment->isLateAllowed();
@endphp

<div class="p-5 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-emerald-500 dark:hover:border-emerald-500 bg-gray-50/50 dark:bg-gray-700/20 transition flex flex-col justify-between shadow-sm">
    <div>
        <div class="flex items-start justify-between gap-2">
            <div>
                <span class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider block">
                    {{ $classroom->name }}
                </span>
                <h4 class="font-bold text-base text-gray-900 dark:text-gray-100 mt-0.5">
                    {{ $test->title }}
                </h4>
            </div>
            <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 text-xs font-bold rounded">
                {{ $test->total_marks }} Marks
            </span>
        </div>

        @if ($test->description)
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-2 line-clamp-2">
                {{ $test->description }}
            </p>
        @endif

        <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400 mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
            @if ($classroom->teacher)
                <span><strong class="text-gray-700 dark:text-gray-300">{{ $classroom->teacher->name }}</strong></span>
                <span>&bull;</span>
            @endif
            <span>Duration: <strong>{{ $test->duration_minutes }} mins</strong></span>
            <span>&bull;</span>
            <span>Questions: <strong>{{ $test->questions->count() }}</strong></span>
        </div>

        <!-- Availability Details Box -->
        <div class="mt-3 text-xs text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 p-2.5 rounded-lg border border-gray-100 dark:border-gray-700 space-y-1">
            <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">Available:</span>
                <strong>{{ $assignment->starts_at ? \Carbon\Carbon::parse($assignment->starts_at)->format('M d, Y h:i A') : 'Immediately' }}</strong>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">Deadline:</span>
                <strong>{{ $assignment->due_at ? \Carbon\Carbon::parse($assignment->due_at)->format('M d, Y h:i A') : 'No deadline' }}</strong>
            </div>
            @if ($assignment->due_at)
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Late Policy:</span>
                    <strong class="{{ $isLateAllowed ? 'text-amber-600 dark:text-amber-400' : 'text-gray-500' }}">
                        {{ $isLateAllowed ? 'Late submission allowed' : 'No late submissions' }}
                    </strong>
                </div>
            @endif
        </div>
    </div>

    <!-- Actions & Status Footer -->
    <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between gap-2">
        @if ($attempt && $attempt->isSubmitted())
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400">
                    Score: {{ $attempt->score }} / {{ $attempt->total_marks }}
                </span>
                @if ($attempt->is_late)
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border border-amber-300 dark:border-amber-700">
                        Submitted Late
                    </span>
                @else
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                        On Time
                    </span>
                @endif
            </div>
            <a href="{{ route('student.classrooms.tests.show', [$classroom, $test]) }}" class="inline-flex items-center text-xs font-semibold px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg text-gray-700 dark:text-gray-200 transition">
                Details &rarr;
            </a>
        @elseif ($attempt && $attempt->isInProgress() && ($isAvailable || $isLateAllowed))
            <span class="text-xs font-bold text-amber-600 flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                In Progress
            </span>
            <a href="{{ route('student.classrooms.tests.take', [$classroom, $test]) }}" class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold shadow-sm transition">
                Resume Exam &rarr;
            </a>
        @elseif ($isUpcoming)
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                Opens {{ $assignment->starts_at->diffForHumans() }}
            </span>
            <a href="{{ route('student.classrooms.tests.show', [$classroom, $test]) }}" class="inline-flex items-center text-xs font-semibold px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg text-gray-700 dark:text-gray-200 transition">
                Schedule &rarr;
            </a>
        @elseif ($isPastDeadline)
            @if ($isLateAllowed)
                <span class="text-xs font-bold text-amber-600 dark:text-amber-400">
                    Late Submission Permitted
                </span>
                <a href="{{ route('student.classrooms.tests.take', [$classroom, $test]) }}" class="inline-flex items-center px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold shadow-sm transition">
                    Start Late Attempt &rarr;
                </a>
            @else
                <span class="text-xs font-semibold text-rose-600 dark:text-rose-400">
                    Deadline Passed &bull; Closed
                </span>
                <a href="{{ route('student.classrooms.tests.show', [$classroom, $test]) }}" class="inline-flex items-center text-xs font-semibold px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg text-gray-700 dark:text-gray-200 transition">
                    Details &rarr;
                </a>
            @endif
        @elseif ($isAvailable)
            <span class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                Open & Ready
            </span>
            <a href="{{ route('student.classrooms.tests.show', [$classroom, $test]) }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-sm transition">
                Take Exam &rarr;
            </a>
        @else
            <span class="text-xs text-gray-500">Unavailable</span>
            <a href="{{ route('student.classrooms.tests.show', [$classroom, $test]) }}" class="text-xs text-gray-500 hover:underline">
                Details &rarr;
            </a>
        @endif
    </div>
</div>
