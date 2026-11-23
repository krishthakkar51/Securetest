<div x-data="{ expanded: false }" class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden bg-gray-50/50 dark:bg-gray-700/20 shadow-sm">
    <div class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white dark:bg-gray-800">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full {{ $isLate ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300' }} flex items-center justify-center font-bold text-sm">
                {{ strtoupper(substr($attempt->student->name, 0, 1)) }}
            </div>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h4 class="font-bold text-sm text-gray-900 dark:text-gray-100">
                        {{ $attempt->student->name }}
                    </h4>
                    @if ($isLate)
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border border-amber-300 dark:border-amber-700">
                            Submitted Late
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                            On Time
                        </span>
                    @endif

                    @if ($attempt->isFullyGraded())
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                            Fully Graded
                        </span>
                    @elseif ($attempt->isPartiallyGraded())
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                            Partially Graded
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300">
                            Ungraded
                        </span>
                    @endif

                    @php
                        $anomalyCount = $attempt->associatedCheatingEvents()->count();
                    @endphp
                    @if ($anomalyCount > 0)
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300 border border-rose-300 dark:border-rose-700">
                            &excl; {{ $anomalyCount }} {{ \Illuminate\Support\Str::plural('Alert', $anomalyCount) }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $attempt->student->email }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3 flex-wrap sm:flex-nowrap">
            <div class="text-right">
                @if ($attempt->negative_marks > 0)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $attempt->final_marks >= ($attempt->total_marks * 0.5) ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' }}">
                        Final: {{ $attempt->final_marks }} / {{ $attempt->total_marks }}
                    </span>
                    <span class="block text-[10px] text-gray-400 mt-0.5">
                        Exam: {{ $attempt->score }} &bull; Deduct: -{{ $attempt->negative_marks }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $attempt->score >= ($attempt->total_marks * 0.5) ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' }}">
                        Score: {{ $attempt->score }} / {{ $attempt->total_marks }}
                    </span>
                @endif
                <span class="block text-[11px] text-gray-400 mt-0.5">
                    Submitted: {{ $attempt->submitted_at ? $attempt->submitted_at->format('M d, Y h:i A') : 'Completed' }}
                </span>
            </div>

            <a
                href="{{ route('teacher.classrooms.tests.attempts.grade', [$classroom, $test, $attempt]) }}"
                class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-sm transition"
            >
                Review Evidence & Grade &rarr;
            </a>

            <button
                type="button"
                @click="expanded = !expanded"
                class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg text-xs font-semibold text-gray-700 dark:text-gray-200 transition"
            >
                <span x-text="expanded ? 'Hide Answers' : 'Review Answers'"></span>
                <svg class="w-4 h-4 ml-1 transition-transform" :class="{'rotate-180': expanded}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Expanded Student Answers View -->
    <div x-show="expanded" class="p-5 border-t border-gray-100 dark:border-gray-700 space-y-4">
        <h5 class="text-xs uppercase font-bold tracking-wider text-gray-500 dark:text-gray-400 mb-2">
            Submitted Student Responses
        </h5>

        @if ($attempt->answers->count() > 0)
            <div class="space-y-3">
                @foreach ($attempt->answers as $aIndex => $ans)
                    <div class="p-3.5 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 text-xs">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <span class="font-bold text-gray-800 dark:text-gray-200">
                                Question {{ $aIndex + 1 }}: {{ $ans->question->question_text }}
                            </span>
                            <span class="px-2 py-0.5 rounded font-mono font-bold {{ $ans->is_correct === true ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : ($ans->is_correct === false ? 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300') }}">
                                {{ $ans->marks_awarded }} / {{ $ans->question->marks }} Marks
                            </span>
                        </div>

                        @if ($ans->question->isMcq())
                            <div class="mt-2 text-gray-700 dark:text-gray-300">
                                <span class="text-gray-500 dark:text-gray-400">Selected Answer:</span>
                                <strong class="font-semibold">{{ $ans->selectedOption ? $ans->selectedOption->option_text : 'No option selected' }}</strong>
                                @if ($ans->is_correct)
                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold ml-1">&check; Correct</span>
                                @else
                                    <span class="text-rose-600 dark:text-rose-400 font-bold ml-1">&cross; Incorrect</span>
                                @endif
                            </div>
                        @else
                            <div class="mt-2">
                                <span class="text-gray-500 dark:text-gray-400 block mb-1">Student Typed Response (Pending Manual Review):</span>
                                <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded border border-gray-100 dark:border-gray-800 text-gray-900 dark:text-gray-100 whitespace-pre-wrap">
                                    {{ $ans->text_answer ?? 'No answer entered.' }}
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-xs text-gray-500 italic">No answers recorded for this attempt.</p>
        @endif
    </div>
</div>
