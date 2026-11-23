<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                        {{ $test->title }}
                    </h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                        Published
                    </span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Created by {{ $test->teacher->name }} &bull; {{ $test->created_at->format('M d, Y h:i A') }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('teacher.tests.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                    + Create Another Test
                </a>
                <a href="{{ route('teacher.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-xs font-semibold rounded-lg transition">
                    &larr; Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Success Alert -->
            @if (session('success'))
                <div class="bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl p-4 text-emerald-800 dark:text-emerald-200 shadow-sm flex items-center">
                    <svg class="w-5 h-5 mr-3 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            <!-- Summary Meta Card -->
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-100 dark:border-gray-700">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                    <div class="p-3 bg-gray-50 dark:bg-gray-700/40 rounded-lg">
                        <span class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Total Duration</span>
                        <p class="text-xl font-extrabold text-gray-900 dark:text-gray-100 mt-1">{{ $test->duration_minutes }} Mins</p>
                    </div>
                    <div class="p-3 bg-gray-50 dark:bg-gray-700/40 rounded-lg">
                        <span class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Total Marks</span>
                        <p class="text-xl font-extrabold text-indigo-600 dark:text-indigo-400 mt-1">{{ $test->total_marks }}</p>
                    </div>
                    <div class="p-3 bg-gray-50 dark:bg-gray-700/40 rounded-lg">
                        <span class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">MCQ Questions</span>
                        <p class="text-xl font-extrabold text-blue-600 dark:text-blue-400 mt-1">{{ $test->mcqCount() }}</p>
                    </div>
                    <div class="p-3 bg-gray-50 dark:bg-gray-700/40 rounded-lg">
                        <span class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Text Questions</span>
                        <p class="text-xl font-extrabold text-teal-600 dark:text-teal-400 mt-1">{{ $test->textCount() }}</p>
                    </div>
                </div>

                @if ($test->description)
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-300">
                        <span class="font-semibold text-gray-700 dark:text-gray-200">Instructions:</span> {{ $test->description }}
                    </div>
                @endif
            </div>

            <!-- Questions Breakdown -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                        Exam Questions ({{ $test->questions->count() }})
                    </h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Auto-graded MCQs + Teacher-reviewed Written Answers
                    </span>
                </div>

                @foreach ($test->questions as $index => $question)
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-100 dark:border-gray-700">
                        
                        <!-- Question Header -->
                        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3 mb-4">
                            <div class="flex items-center gap-2">
                                <span class="w-7 h-7 flex items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 text-xs font-bold">
                                    {{ $index + 1 }}
                                </span>
                                @if ($question->isMcq())
                                    <span class="px-2.5 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                        Multiple Choice Question
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded text-xs font-semibold bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300">
                                        Text-Answer Question (Written)
                                    </span>
                                @endif
                            </div>

                            <span class="text-xs font-bold px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                {{ $question->marks }} Mark{{ $question->marks > 1 ? 's' : '' }}
                            </span>
                        </div>

                        <!-- Question Text -->
                        <div class="text-base font-medium text-gray-900 dark:text-gray-100 mb-4 leading-relaxed">
                            {{ $question->question_text }}
                        </div>

                        <!-- If MCQ: Render Options -->
                        @if ($question->isMcq())
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                                @foreach ($question->options as $optIdx => $option)
                                    <div class="p-3 rounded-lg border flex items-center justify-between {{ $option->is_correct ? 'bg-emerald-50 border-emerald-300 dark:bg-emerald-950/30 dark:border-emerald-700' : 'bg-gray-50 border-gray-200 dark:bg-gray-700/30 dark:border-gray-700' }}">
                                        <div class="flex items-center gap-2.5">
                                            <span class="w-6 h-6 flex items-center justify-center rounded-md font-bold text-xs {{ $option->is_correct ? 'bg-emerald-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600' }}">
                                                {{ chr(65 + $optIdx) }}
                                            </span>
                                            <span class="text-sm {{ $option->is_correct ? 'font-semibold text-emerald-900 dark:text-emerald-200' : 'text-gray-700 dark:text-gray-300' }}">
                                                {{ $option->option_text }}
                                            </span>
                                        </div>
                                        @if ($option->is_correct)
                                            <span class="inline-flex items-center text-xs font-bold text-emerald-700 dark:text-emerald-400 gap-1">
                                                <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                Correct Answer
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <!-- If Text Question: Display Student Response Field Preview -->
                            <div class="bg-gray-50 dark:bg-gray-700/30 p-4 rounded-xl border border-dashed border-gray-300 dark:border-gray-600">
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">
                                    Student Text-Answer Input Area:
                                </label>
                                <textarea disabled rows="3" placeholder="Student will type their answer here during the active examination..."
                                          class="w-full text-sm bg-white dark:bg-gray-800 text-gray-400 italic rounded-lg border-gray-200 dark:border-gray-700 cursor-not-allowed shadow-inner"></textarea>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                    &bull; Students type their response in this field. After exam submission, their exact answer is preserved for instructor review and grading.
                                </p>
                            </div>
                        @endif

                    </div>
                @endforeach
            </div>

        </div>
    </div>
</x-app-layout>
