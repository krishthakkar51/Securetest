<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('teacher.classrooms.show', $classroom) }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                        &larr; {{ $classroom->name }}
                    </a>
                </div>
                <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight mt-1">
                    Results: {{ $test->title }}
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Student submissions, breakdown by submission status, and proctoring responses.
                </p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                @if ($assignment->isPastDeadline())
                    <a href="{{ route('teacher.classrooms.tests.marks', [$classroom, $test]) }}"
                       class="inline-flex items-center text-xs font-bold px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-sm transition">
                        📊 Student Marks List
                    </a>
                    <a href="{{ route('teacher.classrooms.tests.marks.export-csv', [$classroom, $test]) }}"
                       class="inline-flex items-center text-xs font-bold px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg shadow-sm transition">
                        📥 Download CSV
                    </a>
                @endif
                <a href="{{ route('teacher.classrooms.show', $classroom) }}" class="inline-flex items-center text-xs font-semibold px-3.5 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg text-gray-700 dark:text-gray-200 transition">
                    Back to Classroom
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ activeTab: '{{ request('tab', $submittedOnTime->count() > 0 ? 'on_time' : ($submittedLate->count() > 0 ? 'late' : ($inProgress->count() > 0 ? 'in_progress' : 'not_started'))) }}' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl text-emerald-800 dark:text-emerald-300 text-sm flex items-center justify-between shadow-sm">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-emerald-600 dark:text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="font-semibold">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if ($assignment->isPastDeadline())
                <!-- Post-Deadline Marks Banner -->
                <div class="p-4 bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-800 rounded-2xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-lg shrink-0">
                            📊
                        </div>
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-900 dark:text-indigo-200">
                                Assessment Closed &bull; Official Student Marks Available
                            </h4>
                            <p class="text-xs text-indigo-700 dark:text-indigo-300">
                                The deadline has passed. View the complete marks list, adjust negative marks deductions, and download the Excel-compatible marks report.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0 flex-wrap">
                        <a href="{{ route('teacher.classrooms.tests.marks', [$classroom, $test]) }}"
                           class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                            View Marks List &rarr;
                        </a>
                        <a href="{{ route('teacher.classrooms.tests.marks.export-csv', [$classroom, $test]) }}"
                           class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                            Download CSV
                        </a>
                    </div>
                </div>
            @endif

            <!-- Test Summary Banner: 4 Breakdown Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                <button type="button" @click="activeTab = 'on_time'"
                        class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border transition text-left"
                        :class="activeTab === 'on_time' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">On Time</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    </div>
                    <span class="block text-2xl font-black text-gray-900 dark:text-gray-100 mt-1">{{ $submittedOnTime->count() }}</span>
                    <span class="text-[11px] text-gray-400">Submitted on time</span>
                </button>

                <button type="button" @click="activeTab = 'late'"
                        class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border transition text-left"
                        :class="activeTab === 'late' ? 'border-amber-500 ring-2 ring-amber-500/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Late</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    </div>
                    <span class="block text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ $submittedLate->count() }}</span>
                    <span class="text-[11px] text-gray-400">Submitted past deadline</span>
                </button>

                <button type="button" @click="activeTab = 'in_progress'"
                        class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border transition text-left"
                        :class="activeTab === 'in_progress' ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">In Progress</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                    </div>
                    <span class="block text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ $inProgress->count() }}</span>
                    <span class="text-[11px] text-gray-400">Currently taking exam</span>
                </button>

                <button type="button" @click="activeTab = 'not_started'"
                        class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border transition text-left"
                        :class="activeTab === 'not_started' ? 'border-purple-500 ring-2 ring-purple-500/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Not Started</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                    </div>
                    <span class="block text-2xl font-black text-gray-700 dark:text-gray-300 mt-1">{{ $unattemptedStudents->count() }}</span>
                    <span class="text-[11px] text-gray-400">Enrolled pending</span>
                </button>
            </div>

            <!-- Tab Navigation Bar -->
            <div class="flex items-center border-b border-gray-200 dark:border-gray-700 space-x-1 sm:space-x-4">
                <button
                    type="button"
                    @click="activeTab = 'on_time'"
                    class="py-3 px-4 text-xs font-bold uppercase tracking-wider transition border-b-2"
                    :class="activeTab === 'on_time' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400 dark:border-emerald-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                >
                    Submitted On Time ({{ $submittedOnTime->count() }})
                </button>
                <button
                    type="button"
                    @click="activeTab = 'late'"
                    class="py-3 px-4 text-xs font-bold uppercase tracking-wider transition border-b-2"
                    :class="activeTab === 'late' ? 'border-amber-600 text-amber-600 dark:text-amber-400 dark:border-amber-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                >
                    Submitted Late ({{ $submittedLate->count() }})
                </button>
                <button
                    type="button"
                    @click="activeTab = 'in_progress'"
                    class="py-3 px-4 text-xs font-bold uppercase tracking-wider transition border-b-2"
                    :class="activeTab === 'in_progress' ? 'border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                >
                    In Progress ({{ $inProgress->count() }})
                </button>
                <button
                    type="button"
                    @click="activeTab = 'not_started'"
                    class="py-3 px-4 text-xs font-bold uppercase tracking-wider transition border-b-2"
                    :class="activeTab === 'not_started' ? 'border-purple-600 text-purple-600 dark:text-purple-400 dark:border-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                >
                    Not Started ({{ $unattemptedStudents->count() }})
                </button>
            </div>

            <!-- Tab 1: Submitted On Time -->
            <div x-show="activeTab === 'on_time'" class="space-y-4">
                @if ($submittedOnTime->count() > 0)
                    @include('teacher.classrooms.partials.submissions-table', ['attempts' => $submittedOnTime, 'classroom' => $classroom, 'test' => $test, 'isLate' => false])
                @else
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-8 text-center border border-gray-200 dark:border-gray-700 shadow-sm">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">No on-time submissions recorded yet.</p>
                    </div>
                @endif
            </div>

            <!-- Tab 2: Submitted Late -->
            <div x-show="activeTab === 'late'" class="space-y-4">
                @if ($submittedLate->count() > 0)
                    @include('teacher.classrooms.partials.submissions-table', ['attempts' => $submittedLate, 'classroom' => $classroom, 'test' => $test, 'isLate' => true])
                @else
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-8 text-center border border-gray-200 dark:border-gray-700 shadow-sm">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">No late submissions recorded.</p>
                    </div>
                @endif
            </div>

            <!-- Tab 3: In Progress -->
            <div x-show="activeTab === 'in_progress'" class="space-y-4">
                @if ($inProgress->count() > 0)
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
                                        Exam Status
                                    </th>
                                    <th scope="col" class="px-4 py-3.5 border-b border-gray-300 dark:border-gray-700 text-left">
                                        Started At
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-300 dark:divide-gray-700 bg-white dark:bg-gray-800 text-xs text-gray-700 dark:text-gray-300">
                                @foreach ($inProgress as $attempt)
                                    <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/40 transition">
                                        <td class="px-4 py-3.5 border-r border-gray-300 dark:border-gray-700 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 flex items-center justify-center font-bold text-xs shrink-0">
                                                    {{ strtoupper(substr($attempt->student->name, 0, 1)) }}
                                                </div>
                                                <span class="font-bold text-sm text-gray-900 dark:text-gray-100">
                                                    {{ $attempt->student->name }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3.5 border-r border-gray-300 dark:border-gray-700 whitespace-nowrap">
                                            <span class="font-mono text-xs text-gray-600 dark:text-gray-300">
                                                {{ $attempt->student->email }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5 border-r border-gray-300 dark:border-gray-700 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                                <span class="w-2 h-2 rounded-full bg-blue-600 mr-1.5 animate-ping"></span>
                                                Taking Exam Now
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            <span class="font-medium text-gray-800 dark:text-gray-200">
                                                {{ $attempt->started_at ? $attempt->started_at->format('M d, Y h:i A') : 'Recently' }}
                                            </span>
                                            @if ($attempt->started_at)
                                                <span class="block text-[11px] text-gray-400">
                                                    Started {{ $attempt->started_at->diffForHumans() }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-8 text-center border border-gray-200 dark:border-gray-700 shadow-sm">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">No students are currently taking this examination.</p>
                    </div>
                @endif
            </div>

            <!-- Tab 4: Not Started -->
            <div x-show="activeTab === 'not_started'" class="space-y-4">
                @if ($unattemptedStudents->count() > 0)
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
                                    <th scope="col" class="px-4 py-3.5 border-b border-gray-300 dark:border-gray-700 text-left">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-300 dark:divide-gray-700 bg-white dark:bg-gray-800 text-xs text-gray-700 dark:text-gray-300">
                                @foreach ($unattemptedStudents as $pendingStudent)
                                    <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/40 transition">
                                        <td class="px-4 py-3.5 border-r border-gray-300 dark:border-gray-700 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 flex items-center justify-center font-bold text-xs shrink-0">
                                                    {{ strtoupper(substr($pendingStudent->name, 0, 1)) }}
                                                </div>
                                                <span class="font-bold text-sm text-gray-900 dark:text-gray-100">
                                                    {{ $pendingStudent->name }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3.5 border-r border-gray-300 dark:border-gray-700 whitespace-nowrap">
                                            <span class="font-mono text-xs text-gray-600 dark:text-gray-300">
                                                {{ $pendingStudent->email }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            <span class="px-2.5 py-0.5 rounded text-[10px] font-semibold bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                                Pending Submission
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-8 text-center border border-gray-200 dark:border-gray-700 shadow-sm">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">All enrolled students have started or submitted this exam!</p>
                    </div>
                @endif
            </div>


        </div>
    </div>
</x-app-layout>
