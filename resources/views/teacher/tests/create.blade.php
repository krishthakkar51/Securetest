<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                    {{ __('Create Examination') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Design a test containing Multiple Choice (MCQ) and Text-Answer questions.
                </p>
            </div>
            <a href="{{ isset($classroom) ? route('teacher.classrooms.show', $classroom) : route('teacher.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg transition">
                &larr; {{ isset($classroom) ? 'Back to ' . $classroom->name : 'Back to Dashboard' }}
            </a>
        </div>
    </x-slot>

    <div class="py-8" x-data="testBuilder()">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Validation Errors Banner -->
            @if ($errors->any())
                <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl p-5 text-red-800 dark:text-red-200 shadow-sm">
                    <div class="flex items-center font-semibold mb-2">
                        <svg class="w-5 h-5 mr-2 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        Please correct the following errors:
                    </div>
                    <ul class="list-disc list-inside space-y-1 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ isset($classroom) ? route('teacher.classrooms.tests.store-direct', $classroom) : route('teacher.tests.store') }}" @submit="validateBeforeSubmit($event)">
                @csrf

                <!-- Test Details Card -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-100 dark:border-gray-700 space-y-5">
                    <div class="border-b border-gray-100 dark:border-gray-700 pb-3">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                            1. Test Information
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Specify the exam title, instructions, and duration limit.</p>
                    </div>

                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Test Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="title" name="title" value="{{ old('title') }}" required placeholder="e.g. Midterm Computer Networks & Security"
                               class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Instructions / Description (Optional)
                        </label>
                        <textarea id="description" name="description" rows="2" placeholder="e.g. Answer all questions. Camera monitoring and fullscreen mode are strictly enforced."
                                  class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">{{ old('description') }}</textarea>
                    </div>

                    <!-- Duration & Marks Summary -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="duration_minutes" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Duration (Minutes) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" id="duration_minutes" name="duration_minutes" min="1" max="600" value="{{ old('duration_minutes', 45) }}" required
                                   class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Estimated Total Marks
                            </label>
                            <div class="mt-1 p-2.5 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 text-sm font-bold text-indigo-600 dark:text-indigo-400 flex items-center justify-between">
                                <span><span x-text="calculateTotalMarks()"></span> Marks</span>
                                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">(<span x-text="questions.length"></span> Questions)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Classroom Assignment & Schedule Card -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-100 dark:border-gray-700 space-y-5">
                    <div class="border-b border-gray-100 dark:border-gray-700 pb-3">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                            2. Classroom Assignment & Availability (Optional)
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Assign this examination directly to a classroom and set its start time, deadline, and late submission rules.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Select Classroom -->
                        @if (isset($classroom))
                            <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">
                            <div class="md:col-span-1">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    Target Classroom
                                </label>
                                <div class="mt-1 p-2.5 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-800 text-sm font-bold text-indigo-700 dark:text-indigo-300 flex items-center justify-between">
                                    <span class="truncate">{{ $classroom->name }}</span>
                                    <span class="text-xs px-2 py-0.5 rounded bg-indigo-200/60 dark:bg-indigo-800 text-indigo-800 dark:text-indigo-200 shrink-0">Locked Context</span>
                                </div>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                                    Examination will be directly attached to this classroom on creation.
                                </p>
                            </div>
                        @else
                            <div class="md:col-span-1">
                                <label for="classroom_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    Target Classroom
                                </label>
                                <select id="classroom_id" name="classroom_id"
                                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm text-sm">
                                    <option value="">-- Do not assign yet --</option>
                                    @foreach ($classrooms as $c)
                                        <option value="{{ $c->id }}" {{ old('classroom_id') == $c->id ? 'selected' : '' }}>
                                            {{ $c->name }} (Code: {{ $c->code }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                                    Enrolled students in this classroom will immediately see this test.
                                </p>
                            </div>
                        @endif

                        <!-- Starts At -->
                        <div>
                            <label for="starts_at" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Available From
                            </label>
                            <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at') }}"
                                   class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm text-sm">
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                                Leave blank to make available immediately.
                            </p>
                        </div>

                        <!-- Due At -->
                        <div>
                            <label for="due_at" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Due / Deadline Date
                            </label>
                            <input type="datetime-local" id="due_at" name="due_at" value="{{ old('due_at') }}"
                                   class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm text-sm">
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                                Leave blank for no submission deadline.
                            </p>
                        </div>
                    </div>

                    <!-- Late Submissions Checkbox -->
                    <div class="pt-2">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="allow_late_submissions" value="1" {{ old('allow_late_submissions') ? 'checked' : '' }}
                                   class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300 font-medium">
                                Allow late submissions after deadline
                            </span>
                        </label>
                        <p class="text-xs text-gray-500 dark:text-gray-400 ml-6 mt-0.5">
                            If enabled, students can still attempt the exam after the deadline passes, but their submission will be prominently labeled as "Submitted Late".
                        </p>
                    </div>
                </div>

                <!-- Questions Section -->
                <div class="mt-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                3. Exam Questions
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Add multiple choice or written text questions to this test.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="addQuestion('mcq')"
                                    class="inline-flex items-center px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                + Add MCQ
                            </button>
                            <button type="button" @click="addQuestion('text')"
                                    class="inline-flex items-center px-3.5 py-2 bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                + Add Text Question
                            </button>
                        </div>
                    </div>

                    <!-- Questions Loop -->
                    <template x-for="(q, qIndex) in questions" :key="q.id">
                        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-100 dark:border-gray-700 transition hover:border-indigo-300 dark:hover:border-indigo-600">
                            
                            <!-- Question Header -->
                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3 mb-4">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 flex items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 text-xs font-bold"
                                          x-text="qIndex + 1"></span>
                                    
                                    <!-- Question Type Badge & Selector -->
                                    <select :name="'questions[' + qIndex + '][type]'" x-model="q.type"
                                            class="text-xs font-semibold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 py-1 pl-2.5 pr-8 focus:ring-indigo-500">
                                        <option value="mcq">Multiple Choice (MCQ)</option>
                                        <option value="text">Text Answer (Written)</option>
                                    </select>
                                </div>

                                <div class="flex items-center gap-3">
                                    <!-- Marks Input -->
                                    <div class="flex items-center gap-1.5">
                                        <label class="text-xs text-gray-500 dark:text-gray-400 font-medium">Marks:</label>
                                        <input type="number" onwheel="this.blur()" :name="'questions[' + qIndex + '][marks]'" x-model.number="q.marks" min="1" max="100" required
                                               class="w-16 text-center text-xs font-bold rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 py-1 focus:ring-indigo-500">
                                    </div>

                                    <!-- Remove Question -->
                                    <button type="button" @click="removeQuestion(qIndex)" :disabled="questions.length <= 1"
                                            class="text-gray-400 hover:text-red-500 disabled:opacity-30 disabled:cursor-not-allowed transition p-1" title="Remove Question">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Question Prompt Field -->
                            <div class="mb-4">
                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1">
                                    Question Text <span class="text-red-500">*</span>
                                </label>
                                <textarea :name="'questions[' + qIndex + '][text]'" x-model="q.text" rows="2" required
                                          :placeholder="q.type === 'mcq' ? 'e.g. Which layer of the OSI model does the TCP protocol belong to?' : 'e.g. Explain the difference between symmetric and asymmetric encryption.'"
                                          class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"></textarea>
                            </div>

                            <!-- MCQ Options Sub-section -->
                            <div x-show="q.type === 'mcq'" class="space-y-3 bg-gray-50 dark:bg-gray-700/30 p-4 rounded-xl border border-gray-100 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                        Answer Options & Correct Key
                                    </span>
                                    <span class="text-xs text-indigo-600 dark:text-indigo-400 font-medium">
                                        Select the radio button next to the correct answer.
                                    </span>
                                </div>

                                <div class="space-y-2">
                                    <template x-for="(opt, optIdx) in q.options" :key="optIdx">
                                        <div class="flex items-center gap-3">
                                            <!-- Correct Option Radio -->
                                            <label class="flex items-center cursor-pointer" :title="'Mark Option ' + String.fromCharCode(65 + optIdx) + ' as correct'">
                                                <input type="radio" :name="'questions[' + qIndex + '][correct_option]'" :value="optIdx"
                                                       x-model.number="q.correct_option" required
                                                       class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                            </label>

                                            <!-- Option Letter Badge -->
                                            <span class="w-6 h-6 flex items-center justify-center rounded-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 text-xs font-bold text-gray-700 dark:text-gray-300"
                                                  x-text="String.fromCharCode(65 + optIdx)"></span>

                                            <!-- Option Text Input -->
                                            <input type="text" :name="'questions[' + qIndex + '][options][' + optIdx + '][text]'"
                                                   x-model="opt.text" :required="q.type === 'mcq'"
                                                   :placeholder="'Option ' + String.fromCharCode(65 + optIdx)"
                                                   class="flex-1 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 py-1.5 shadow-sm">

                                            <!-- Remove Option Button -->
                                            <button type="button" @click="removeOption(qIndex, optIdx)" :disabled="q.options.length <= 2"
                                                    class="text-gray-400 hover:text-red-500 disabled:opacity-20 disabled:cursor-not-allowed transition p-1" title="Remove Option">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>

                                <div class="pt-2">
                                    <button type="button" @click="addOption(qIndex)" :disabled="q.options.length >= 6"
                                            class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 inline-flex items-center gap-1 disabled:opacity-40">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Add Another Option
                                    </button>
                                </div>
                            </div>

                            <!-- Text-Answer Preview Sub-section -->
                            <div x-show="q.type === 'text'" class="bg-teal-50 dark:bg-teal-900/20 p-4 rounded-xl border border-teal-200 dark:border-teal-800/50">
                                <div class="flex items-start gap-3">
                                    <div class="p-2 bg-teal-100 dark:bg-teal-800/40 rounded-lg text-teal-700 dark:text-teal-300 mt-0.5">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-xs font-bold text-teal-800 dark:text-teal-200 uppercase tracking-wider">
                                            Text-Answer Question (Student Typed Response)
                                        </h4>
                                        <p class="text-xs text-teal-700 dark:text-teal-300 mt-1">
                                            Students will answer this question by typing their free-form response into a multi-line text input field during the exam. Responses are saved and presented in the Teacher Review portal for manual mark assignment.
                                        </p>
                                        <div class="mt-3 p-3 bg-white dark:bg-gray-800 rounded-lg border border-dashed border-teal-300 dark:border-teal-700 text-xs text-gray-400 italic">
                                            [Student response text field will appear here during the exam]
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </template>
                </div>

                <!-- Action Buttons -->
                <div class="mt-8 flex items-center justify-end gap-4 border-t border-gray-100 dark:border-gray-700 pt-6">
                    <a href="{{ route('teacher.dashboard') }}" class="px-5 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-lg shadow-md transition hover:shadow-lg">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Save & Publish Examination
                    </button>
                </div>

            </form>

        </div>
    </div>

    <!-- Dynamic Alpine Builder Script -->
    <script>
        function testBuilder() {
            return {
                questions: [
                    {
                        id: 1,
                        type: 'mcq',
                        text: '',
                        marks: 1,
                        correct_option: 0,
                        options: [
                            { text: '' },
                            { text: '' },
                            { text: '' },
                            { text: '' }
                        ]
                    },
                    {
                        id: 2,
                        type: 'text',
                        text: '',
                        marks: 5,
                        correct_option: 0,
                        options: [
                            { text: '' },
                            { text: '' }
                        ]
                    }
                ],

                addQuestion(type = 'mcq') {
                    this.questions.push({
                        id: Date.now() + Math.random(),
                        type: type,
                        text: '',
                        marks: type === 'mcq' ? 1 : 5,
                        correct_option: 0,
                        options: [
                            { text: '' },
                            { text: '' },
                            { text: '' },
                            { text: '' }
                        ]
                    });
                },

                removeQuestion(index) {
                    if (this.questions.length > 1) {
                        this.questions.splice(index, 1);
                    }
                },

                addOption(qIndex) {
                    if (this.questions[qIndex].options.length < 6) {
                        this.questions[qIndex].options.push({ text: '' });
                    }
                },

                removeOption(qIndex, optIdx) {
                    if (this.questions[qIndex].options.length > 2) {
                        this.questions[qIndex].options.splice(optIdx, 1);
                        if (this.questions[qIndex].correct_option >= this.questions[qIndex].options.length) {
                            this.questions[qIndex].correct_option = 0;
                        }
                    }
                },

                calculateTotalMarks() {
                    return this.questions.reduce((sum, q) => sum + (Number(q.marks) || 0), 0);
                },

                validateBeforeSubmit(event) {
                    for (let i = 0; i < this.questions.length; i++) {
                        const q = this.questions[i];
                        if (!q.text.trim()) {
                            alert('Please enter text for Question #' + (i + 1));
                            event.preventDefault();
                            return false;
                        }

                        if (q.type === 'mcq') {
                            for (let j = 0; j < q.options.length; j++) {
                                if (!q.options[j].text.trim()) {
                                    alert('Please fill out Option ' + String.fromCharCode(65 + j) + ' for Question #' + (i + 1));
                                    event.preventDefault();
                                    return false;
                                }
                            }
                        }
                    }
                    return true;
                }
            };
        }
    </script>
</x-app-layout>
