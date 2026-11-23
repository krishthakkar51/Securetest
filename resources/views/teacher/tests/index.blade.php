<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                    {{ __('Manage Examinations') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    All tests created with multiple choice and text-answer questions.
                </p>
            </div>
            <a href="{{ route('teacher.tests.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create New Test
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl p-4 text-emerald-800 dark:text-emerald-200 shadow-sm flex items-center">
                    <svg class="w-5 h-5 mr-3 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden border border-gray-100 dark:border-gray-700">
                @if ($tests->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-700/50 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-gray-700">
                                    <th class="py-3 px-6">Test Title</th>
                                    <th class="py-3 px-6">Questions</th>
                                    <th class="py-3 px-6">Duration</th>
                                    <th class="py-3 px-6">Total Marks</th>
                                    <th class="py-3 px-6">Submissions</th>
                                    <th class="py-3 px-6">Created At</th>
                                    <th class="py-3 px-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                                @foreach ($tests as $test)
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition">
                                        <td class="py-4 px-6 font-semibold text-gray-900 dark:text-gray-100">
                                            <a href="{{ route('teacher.tests.show', $test) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                                {{ $test->title }}
                                            </a>
                                        </td>
                                        <td class="py-4 px-6 text-gray-600 dark:text-gray-300">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300">
                                                {{ $test->questions_count }} Question{{ $test->questions_count > 1 ? 's' : '' }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-gray-600 dark:text-gray-300">
                                            {{ $test->duration_minutes }} mins
                                        </td>
                                        <td class="py-4 px-6 font-bold text-indigo-600 dark:text-indigo-400">
                                            {{ $test->total_marks }}
                                        </td>
                                        <td class="py-4 px-6 text-gray-600 dark:text-gray-300">
                                            {{ $test->attempts_count }}
                                        </td>
                                        <td class="py-4 px-6 text-gray-500 dark:text-gray-400 text-xs">
                                            {{ $test->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="py-4 px-6 text-right">
                                            <a href="{{ route('teacher.tests.show', $test) }}" class="inline-flex items-center text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-200">
                                                View Details &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                        {{ $tests->links() }}
                    </div>
                @else
                    <div class="p-12 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h4 class="text-base font-semibold text-gray-800 dark:text-gray-200">No tests created yet</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                            Start by creating your first test with multiple-choice and written text questions.
                        </p>
                        <div class="mt-4">
                            <a href="{{ route('teacher.tests.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                                + Create First Test
                            </a>
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
