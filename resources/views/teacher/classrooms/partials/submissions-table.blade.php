{{-- resources/views/teacher/classrooms/partials/submissions-table.blade.php --}}
@props(['attempts', 'classroom', 'test', 'isLate' => false])

<div class="overflow-x-auto rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
    <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700 border-collapse text-left">
        <thead class="bg-gray-100 dark:bg-gray-700/60 text-gray-700 dark:text-gray-200 text-xs font-bold uppercase tracking-wider">
            <tr>
                <th scope="col" class="px-4 py-3.5 border-b border-r border-gray-300 dark:border-gray-700 text-left">
                    Student
                </th>
                <th scope="col" class="px-4 py-3.5 border-b border-r border-gray-300 dark:border-gray-700 text-left">
                    Email
                </th>
                <th scope="col" class="px-4 py-3.5 border-b border-r border-gray-300 dark:border-gray-700 text-left">
                    Submission Status
                </th>
                <th scope="col" class="px-4 py-3.5 border-b border-r border-gray-300 dark:border-gray-700 text-center">
                    Marks / Score
                </th>
                <th scope="col" class="px-4 py-3.5 border-b border-r border-gray-300 dark:border-gray-700 text-left">
                    Submission Date &amp; Time
                </th>
                <th scope="col" class="px-4 py-3.5 border-b border-gray-300 dark:border-gray-700 text-center">
                    Actions
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-300 dark:divide-gray-700 bg-white dark:bg-gray-800 text-xs text-gray-700 dark:text-gray-300">
            @foreach ($attempts as $attempt)
                @php
                    $isGraded = $attempt->isGraded();
                    $anomalyCount = $attempt->cheatingAlertsCount();
                @endphp
                <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/40 transition">
                    {{-- Column 1: Student --}}
                    <td class="px-4 py-3.5 border-r border-gray-300 dark:border-gray-700 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full {{ $isLate ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' }} flex items-center justify-center font-bold text-xs shrink-0">
                                {{ strtoupper(substr($attempt->student->name, 0, 1)) }}
                            </div>
                            <span class="font-bold text-sm text-gray-900 dark:text-gray-100">
                                {{ $attempt->student->name }}
                            </span>
                        </div>
                    </td>

                    {{-- Column 2: Email --}}
                    <td class="px-4 py-3.5 border-r border-gray-300 dark:border-gray-700 whitespace-nowrap">
                        <span class="font-mono text-xs text-gray-600 dark:text-gray-300">
                            {{ $attempt->student->email }}
                        </span>
                    </td>

                    {{-- Column 3: Submission Status --}}
                    <td class="px-4 py-3.5 border-r border-gray-300 dark:border-gray-700">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            @if ($isLate)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border border-amber-300 dark:border-amber-700">
                                    Submitted Late
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
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

                            @if ($anomalyCount > 0)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300 border border-rose-300 dark:border-rose-700">
                                    &excl; {{ $anomalyCount }} {{ \Illuminate\Support\Str::plural('Alert', $anomalyCount) }}
                                </span>
                            @endif
                        </div>
                    </td>

                    {{-- Column 4: Marks / Score --}}
                    <td class="px-4 py-3.5 border-r border-gray-300 dark:border-gray-700 text-center whitespace-nowrap">
                        @if ($attempt->negative_marks > 0)
                            <div class="inline-flex flex-col items-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold font-mono {{ $attempt->final_marks >= ($attempt->total_marks * 0.5) ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' }}">
                                    Final: {{ $attempt->final_marks }} / {{ $attempt->total_marks }}
                                </span>
                                <span class="block text-[10px] text-gray-400 mt-0.5">
                                    Exam: {{ $attempt->score }} &bull; Deduct: -{{ $attempt->negative_marks }}
                                </span>
                            </div>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold font-mono {{ $attempt->score >= ($attempt->total_marks * 0.5) ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' }}">
                                Score: {{ $attempt->score }} / {{ $attempt->total_marks }}
                            </span>
                        @endif
                    </td>

                    {{-- Column 5: Submission Date & Time --}}
                    <td class="px-4 py-3.5 border-r border-gray-300 dark:border-gray-700 whitespace-nowrap">
                        <span class="font-medium text-gray-800 dark:text-gray-200">
                            {{ $attempt->submitted_at ? $attempt->submitted_at->format('M d, Y h:i A') : 'Completed' }}
                        </span>
                        @if ($attempt->submitted_at)
                            <span class="block text-[11px] text-gray-400">
                                {{ $attempt->submitted_at->diffForHumans() }}
                            </span>
                        @endif
                    </td>

                    {{-- Column 6: Actions (Dynamic Button) --}}
                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                        @if ($isGraded)
                            <a
                                href="{{ route('teacher.classrooms.tests.attempts.grade', [$classroom, $test, $attempt]) }}"
                                class="inline-flex items-center px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-sm transition transform hover:-translate-y-0.5"
                            >
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Recheck / Edit Grade &rarr;
                            </a>
                        @else
                            <a
                                href="{{ route('teacher.classrooms.tests.attempts.grade', [$classroom, $test, $attempt]) }}"
                                class="inline-flex items-center px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-sm transition transform hover:-translate-y-0.5"
                            >
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Review &amp; Grade &rarr;
                            </a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
