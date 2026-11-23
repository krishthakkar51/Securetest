<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <a href="{{ route('teacher.classrooms.show', $classroom) }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                        {{ $classroom->name }}
                    </a>
                    <span class="text-xs text-gray-400">&rsaquo;</span>
                    <a href="{{ route('teacher.classrooms.tests.results', [$classroom, $test]) }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                        {{ $test->title }} Results
                    </a>
                    <span class="text-xs text-gray-400">&rsaquo;</span>
                    <span class="text-xs text-gray-600 dark:text-gray-300 font-semibold">Student Marks List</span>
                </div>
                <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight mt-1">
                    Student Marks: {{ $test->title }}
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Official post-deadline student performance report and faculty-controlled negative mark deductions.
                </p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <a href="{{ route('teacher.classrooms.tests.marks.export-csv', [$classroom, $test]) }}"
                   class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition transform hover:-translate-y-0.5">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Download CSV Report (Excel)
                </a>
                <a href="{{ route('teacher.classrooms.tests.results', [$classroom, $test]) }}"
                   class="inline-flex items-center px-3.5 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-200 transition">
                    &larr; Back to Results
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ modalOpen: false, modalAttempt: null, modalStudent: '', modalScore: 0, modalNegative: 0, modalReason: '', modalAudits: [] }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Success Notification -->
            @if (session('success'))
                <div class="p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl text-emerald-800 dark:text-emerald-300 text-sm flex items-center justify-between shadow-sm">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-emerald-600 dark:text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 rounded-xl text-rose-800 dark:text-rose-300 text-sm space-y-1">
                    <p class="font-bold">Please correct the following errors:</p>
                    <ul class="list-disc list-inside text-xs">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Deadline & Assessment Metadata Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300 border border-rose-300">
                                Deadline Passed &bull; Closed
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                Due: <strong>{{ $assignment->due_at ? $assignment->due_at->format('M d, Y h:i A') : 'N/A' }}</strong>
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mt-2">
                            Classroom: {{ $classroom->name }} &bull; Test: {{ $test->title }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Total Exam Marks: <strong>{{ $test->total_marks }}</strong> &bull; Total Enrolled Students: <strong>{{ $marksData->count() }}</strong>
                        </p>
                    </div>

                    <!-- Summary Stats Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/40 rounded-xl border border-gray-100 dark:border-gray-700">
                            <span class="block text-xl font-black text-gray-900 dark:text-gray-100">{{ $marksData->count() }}</span>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Enrolled</span>
                        </div>
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl border border-emerald-100 dark:border-emerald-800">
                            <span class="block text-xl font-black text-emerald-700 dark:text-emerald-300">{{ $submittedCount }}</span>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Submitted</span>
                        </div>
                        <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl border border-indigo-100 dark:border-indigo-800">
                            <span class="block text-xl font-black text-indigo-700 dark:text-indigo-300">{{ $averageFinalMarks }}</span>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Avg Final</span>
                        </div>
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/30 rounded-xl border border-amber-100 dark:border-amber-800">
                            <span class="block text-xl font-black text-amber-700 dark:text-amber-300">{{ $highestFinalMarks }}</span>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Highest</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student Marks Table Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-5 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-base text-gray-900 dark:text-gray-100">
                            Official Student Marks Breakdown
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Final Marks = Exam Marks &minus; Negative Marks. Deductions are strictly teacher-controlled and audited.
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-left text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider text-[11px]">
                            <tr>
                                @if ($hasRollField)
                                    <th scope="col" class="py-3.5 px-4">Index / Roll No</th>
                                @endif
                                <th scope="col" class="py-3.5 px-4">Student Name</th>
                                <th scope="col" class="py-3.5 px-4 text-center">Status</th>
                                <th scope="col" class="py-3.5 px-4 text-right">Original Exam Marks</th>
                                <th scope="col" class="py-3.5 px-4 text-right">Negative Marks</th>
                                <th scope="col" class="py-3.5 px-4 text-right">Final Marks</th>
                                <th scope="col" class="py-3.5 px-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            @forelse ($marksData as $row)
                                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">
                                    @if ($hasRollField)
                                        <td class="py-3.5 px-4 font-mono font-medium text-gray-600 dark:text-gray-300">
                                            {{ $row['roll_number'] ?? '—' }}
                                        </td>
                                    @endif

                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-bold text-xs shrink-0">
                                                {{ strtoupper(substr($row['student']->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <span class="block font-bold text-gray-900 dark:text-gray-100 text-xs">
                                                    {{ $row['student']->name }}
                                                </span>
                                                <span class="block text-[11px] text-gray-400 font-normal">
                                                    {{ $row['student']->email }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="py-3.5 px-4 text-center">
                                        @if ($row['has_attempt'])
                                            @if ($row['is_late'])
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border border-amber-300">
                                                    Submitted Late
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                                    On Time
                                                </span>
                                            @endif
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                Not Attempted
                                            </span>
                                        @endif
                                    </td>

                                    <td class="py-3.5 px-4 text-right font-mono font-semibold text-gray-700 dark:text-gray-300">
                                        @if ($row['has_attempt'])
                                            {{ number_format($row['original_marks'], 2) }}
                                            <span class="text-[10px] text-gray-400">/ {{ $test->total_marks }}</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>

                                    <td class="py-3.5 px-4 text-right font-mono">
                                        @if ($row['has_attempt'])
                                            @if ($row['negative_marks'] > 0)
                                                <span class="text-rose-600 dark:text-rose-400 font-bold" title="{{ $row['negative_marks_reason'] }}">
                                                    &minus;{{ number_format($row['negative_marks'], 2) }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">0.00</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>

                                    <td class="py-3.5 px-4 text-right font-mono font-bold text-sm">
                                        @if ($row['has_attempt'])
                                            <span class="{{ $row['final_marks'] >= ($test->total_marks * 0.5) ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                                {{ number_format($row['final_marks'], 2) }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 text-xs font-normal">0.00</span>
                                        @endif
                                    </td>

                                    <td class="py-3.5 px-4 text-center space-x-2">
                                        @if ($row['has_attempt'])
                                            <button
                                                type="button"
                                                @click="
                                                    modalAttempt = '{{ $row['attempt']->id }}';
                                                    modalStudent = '{{ addslashes($row['student']->name) }}';
                                                    modalScore = {{ $row['original_marks'] }};
                                                    modalNegative = {{ $row['negative_marks'] }};
                                                    modalReason = '{{ addslashes($row['negative_marks_reason'] ?? '') }}';
                                                    modalAudits = {{ json_encode($row['attempt']->markDeductionAudits->map(fn($a) => [
                                                        'date' => $a->created_at->format('M d, Y h:i A'),
                                                        'teacher' => $a->teacher->name,
                                                        'deduction' => $a->negative_marks,
                                                        'final' => $a->final_marks,
                                                        'reason' => $a->reason
                                                    ])) }};
                                                    modalOpen = true;
                                                "
                                                class="inline-flex items-center px-2.5 py-1 bg-amber-50 hover:bg-amber-100 dark:bg-amber-900/30 dark:hover:bg-amber-900/50 text-amber-800 dark:text-amber-200 border border-amber-300 dark:border-amber-700 rounded-lg text-xs font-semibold transition"
                                            >
                                                Adjust Deduction
                                            </button>

                                            <a
                                                href="{{ route('teacher.classrooms.tests.attempts.grade', [$classroom, $test, $row['attempt']]) }}"
                                                class="inline-flex items-center px-2.5 py-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-xs font-semibold transition"
                                            >
                                                Grade / Review
                                            </a>
                                        @else
                                            <span class="text-gray-400 text-[11px] italic">No submission</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-gray-500 dark:text-gray-400 text-sm">
                                        No students are currently enrolled in this classroom.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Negative Marks Adjustment Modal -->
        <div x-show="modalOpen"
             x-cloak
             style="display: none;"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div @click.away="modalOpen = false"
                 class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full p-6 border border-gray-200 dark:border-gray-700 space-y-4">

                <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-bold text-base text-gray-900 dark:text-gray-100">
                        Adjust Negative Marks: <span x-text="modalStudent"></span>
                    </h3>
                    <button type="button" @click="modalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="p-3 bg-gray-50 dark:bg-gray-700/40 rounded-xl text-xs space-y-1">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Original Exam Score:</span>
                        <span class="font-mono font-bold text-gray-800 dark:text-gray-200"><span x-text="modalScore"></span> Marks</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Maximum Allowed Deduction:</span>
                        <span class="font-mono font-bold text-gray-800 dark:text-gray-200"><span x-text="modalScore"></span> Marks</span>
                    </div>
                </div>

                <form :action="'{{ url('/teacher/classrooms/' . $classroom->id . '/tests/' . $test->id . '/attempts') }}/' + modalAttempt + '/negative-marks'" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                            Negative Marks to Deduct (0 &ndash; <span x-text="modalScore"></span>):
                        </label>
                        <input
                            type="number"
                            step="any"
                            min="0"
                            :max="modalScore"
                            name="negative_marks"
                            x-model="modalNegative"
                            required
                            onwheel="this.blur(); event.preventDefault();"
                            class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500 p-2.5 font-mono font-bold marks-input"
                        />
                        <p class="text-[11px] text-gray-400 mt-1">Deduction cannot exceed student's original score.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                            Reason / Audit Explanation (Required):
                        </label>
                        <textarea
                            name="reason"
                            rows="3"
                            x-model="modalReason"
                            required
                            placeholder="Specify reason for deduction (e.g. Verified proctoring alert, manual policy adjustment, unauthorized resource usage)..."
                            class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-xs focus:ring-indigo-500 focus:border-indigo-500 p-2.5"
                        ></textarea>
                    </div>

                    <!-- Audit Log History -->
                    <template x-if="modalAudits && modalAudits.length > 0">
                        <div class="pt-2 border-t border-gray-100 dark:border-gray-700">
                            <h4 class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
                                Audit History (<span x-text="modalAudits.length"></span>)
                            </h4>
                            <div class="max-h-32 overflow-y-auto space-y-2 text-[11px]">
                                <template x-for="(audit, aIndex) in modalAudits" :key="aIndex">
                                    <div class="p-2 bg-gray-50 dark:bg-gray-700/30 rounded border border-gray-100 dark:border-gray-700">
                                        <div class="flex justify-between font-semibold text-gray-700 dark:text-gray-300">
                                            <span>&minus;<span x-text="audit.deduction"></span> marks &rarr; Final: <span x-text="audit.final"></span></span>
                                            <span class="text-gray-400 text-[10px]" x-text="audit.date"></span>
                                        </div>
                                        <p class="text-gray-600 dark:text-gray-400 text-[10px] mt-0.5" x-text="audit.reason"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm">
                            Save Deduction & Record Audit
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Suppress mouse-wheel scrolling on negative marks input to prevent accidental increments/decrements
            const marksInputs = document.querySelectorAll('input[name="negative_marks"], .marks-input');
            marksInputs.forEach(function (input) {
                input.addEventListener('wheel', function (e) {
                    e.preventDefault();
                    this.blur();
                }, { passive: false });
            });
        });
    </script>
</x-app-layout>
