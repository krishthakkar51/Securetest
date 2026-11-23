<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                        {{ $classroom->name }}
                    </h2>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-mono font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                        Code: {{ $classroom->code }}
                    </span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Classroom management, student roster, test assignments, and examination results.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('teacher.classrooms.edit', $classroom) }}" class="inline-flex items-center text-xs font-semibold px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/40 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 rounded-lg border border-indigo-200 dark:border-indigo-800 transition">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit Classroom
                </a>
                <a href="{{ route('teacher.classrooms.index') }}" class="inline-flex items-center text-xs font-semibold px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg text-gray-700 dark:text-gray-200 transition">
                    &larr; All Classrooms
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Alerts -->
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

            @if (session('error'))
                <div class="p-4 bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 rounded-xl text-rose-800 dark:text-rose-300 text-sm flex items-center">
                    <svg class="w-5 h-5 mr-3 text-rose-600 dark:text-rose-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- Classroom Banner with Join Code Card -->
            @php
                $theme = $classroom->bannerTheme();
                $bannerUrl = $classroom->bannerUrl();
            @endphp
            <div class="rounded-2xl shadow-lg p-6 text-white relative overflow-hidden" style="{{ $bannerUrl ? "background-image: url('{$bannerUrl}'); background-size: cover; background-position: center;" : "background: {$theme['bg']};" }}">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-[1px]"></div>
                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <span class="inline-block px-2.5 py-1 rounded bg-white/10 text-indigo-200 text-xs font-semibold uppercase tracking-wider mb-2">
                            Classroom Hub
                        </span>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold">{{ $classroom->name }}</h1>
                            <a href="{{ route('teacher.classrooms.edit', $classroom) }}" class="p-1.5 bg-white/10 hover:bg-white/20 rounded-lg text-indigo-200 hover:text-white transition" title="Rename or Edit Classroom">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </a>
                        </div>
                        @if ($classroom->description)
                            <p class="text-indigo-200 mt-1 text-sm max-w-2xl">
                                {{ $classroom->description }}
                            </p>
                        @endif
                        <div class="flex flex-wrap items-center gap-3 mt-4 text-xs text-indigo-200">
                            <span>Students: <strong class="text-white">{{ $classroom->students->count() }}</strong></span>
                            <span>&bull;</span>
                            <span>Assigned Tests: <strong class="text-white">{{ $classroom->tests->count() }}</strong></span>
                            <span>&bull;</span>
                            @if ($classroom->is_email_restricted)
                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-indigo-900/60 text-indigo-100 font-mono text-[11px] border border-indigo-400/30">
                                    Restriction: {{ $classroom->allowed_email_domain }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-white/10 text-indigo-200 text-[11px]">
                                    Open Enrollment
                                </span>
                            @endif
                            <span>&bull;</span>
                            <span>Created: <strong class="text-white">{{ $classroom->created_at->format('M d, Y') }}</strong></span>
                        </div>
                    </div>

                    <!-- Join Code Display Box -->
                    <div class="bg-white/10 backdrop-blur-md border border-white/20 p-5 rounded-xl flex flex-col items-center justify-center text-center min-w-[240px]">
                        <span class="text-xs font-semibold text-indigo-200 uppercase tracking-widest">Student Join Code</span>
                        <div class="flex items-center gap-2 mt-2">
                            <span id="classroomCodeDisplay" class="text-3xl font-mono font-extrabold tracking-widest text-white select-all">
                                {{ $classroom->code }}
                            </span>
                            <button
                                type="button"
                                onclick="navigator.clipboard.writeText('{{ $classroom->code }}'); alert('Join code copied to clipboard: {{ $classroom->code }}');"
                                class="p-1.5 hover:bg-white/20 rounded-lg text-indigo-200 hover:text-white transition"
                                title="Copy Join Code"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                        </div>
                        <p class="text-[11px] text-indigo-300 mt-2">
                            Share this code with students to let them join this classroom.
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-6">

                    <!-- Classroom Stream & Discussion Feature -->
                    @include('classrooms.partials.discussion-stream')

                    <!-- Classroom Study Materials Card -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                    Classroom Study Materials
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Upload lecture notes, reference PDFs, slides, and syllabus for enrolled students.
                                </p>
                            </div>
                            <button
                                type="button"
                                onclick="document.getElementById('uploadMaterialFormContainer').classList.toggle('hidden');"
                                class="inline-flex items-center px-3 py-1.5 bg-stone-800 hover:bg-stone-900 text-stone-100 dark:bg-stone-100 dark:hover:bg-white dark:text-stone-900 text-xs font-bold rounded-lg shadow-sm transition"
                            >
                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                + Upload Material
                            </button>
                        </div>

                        <!-- Upload Form (Collapsible) -->
                        <div id="uploadMaterialFormContainer" class="hidden mb-6 p-4 rounded-xl bg-stone-50 dark:bg-stone-900/60 border border-stone-200 dark:border-stone-700">
                            <form method="POST" action="{{ route('teacher.classrooms.materials.upload', $classroom) }}" enctype="multipart/form-data" class="space-y-4">
                                @csrf
                                <div>
                                    <x-input-label for="material_title" :value="__('Title *')" />
                                    <x-text-input id="material_title" name="title" type="text" class="mt-1 block w-full text-sm" required placeholder="e.g. Lecture 1 - Concurrency & Threads Overview" />
                                </div>
                                <div>
                                    <x-input-label for="material_desc" :value="__('Description (Optional)')" />
                                    <textarea id="material_desc" name="description" rows="2" class="mt-1 block w-full text-xs rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:ring-amber-500 focus:border-amber-500" placeholder="Optional context or instructions regarding this material..."></textarea>
                                </div>
                                <div>
                                    <x-input-label for="material_file" :value="__('Select File (PDF, DOC/X, PPT/X, XLS/X, TXT, Images - Max 20MB) *')" />
                                    <input id="material_file" name="file" type="file" required class="mt-1 block w-full text-xs text-stone-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-stone-200 file:text-stone-800 hover:file:bg-stone-300 dark:file:bg-stone-700 dark:file:text-stone-200 transition" />
                                </div>
                                <div class="flex items-center justify-end gap-2 pt-2">
                                    <button type="button" onclick="document.getElementById('uploadMaterialFormContainer').classList.add('hidden');" class="px-3 py-1.5 text-xs text-stone-600 hover:text-stone-900">
                                        Cancel
                                    </button>
                                    <button type="submit" class="px-4 py-2 bg-stone-800 hover:bg-stone-900 text-stone-100 font-bold text-xs rounded-lg shadow transition">
                                        Upload &amp; Attach
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Materials List -->
                        @if ($classroom->materials->count() > 0)
                            <div class="space-y-3">
                                @foreach ($classroom->materials as $mat)
                                    <div class="p-3.5 rounded-xl border border-stone-200 dark:border-stone-700/80 bg-stone-50/50 dark:bg-stone-800/40 flex items-center justify-between gap-4 hover:border-stone-400 transition">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="w-10 h-10 rounded-lg flex items-center justify-center text-xs font-bold shrink-0 uppercase {{ $mat->typeBadgeColor() }}">
                                                {{ $mat->extension() }}
                                            </span>
                                            <div class="min-w-0">
                                                <h4 class="font-bold text-sm text-stone-900 dark:text-stone-100 truncate">
                                                    {{ $mat->title }}
                                                </h4>
                                                <p class="text-[11px] text-stone-500 truncate">
                                                    {{ $mat->original_filename }} &bull; {{ $mat->formattedSize() }} &bull; Uploaded {{ $mat->created_at->format('M d, Y') }}
                                                </p>
                                                @if ($mat->description)
                                                    <p class="text-xs text-stone-600 dark:text-stone-400 mt-1 line-clamp-1">
                                                        {{ $mat->description }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2 shrink-0">
                                            <a href="{{ route('teacher.classrooms.materials.download', [$classroom, $mat]) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-stone-100 hover:bg-stone-200 dark:bg-stone-700 dark:hover:bg-stone-600 text-stone-800 dark:text-stone-200 transition" title="Download Material">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                Download
                                            </a>

                                            <form method="POST" action="{{ route('teacher.classrooms.materials.delete', [$classroom, $mat]) }}" onsubmit="return confirm('Are you sure you want to delete this study material?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 rounded-lg transition" title="Delete Material">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="border-2 border-dashed border-stone-200 dark:border-stone-700 rounded-xl p-6 text-center">
                                <p class="text-xs text-stone-500 dark:text-stone-400">No study materials uploaded for this classroom yet.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Assigned Tests Card -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                    Assigned Examinations
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Only tests assigned to this classroom are accessible to its enrolled students.
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('teacher.classrooms.tests.create', $classroom) }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    + Create Examination
                                </a>
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                    {{ $classroom->tests->count() }} Assigned
                                </span>
                            </div>
                        </div>

                        @if ($classroom->tests->count() > 0)
                            <div class="space-y-4">
                                @foreach ($classroom->tests as $test)
                                    @php
                                        $pivot = $test->pivot;
                                        $submissions = $submissionCounts[$test->id] ?? 0;
                                    @endphp
                                    <div class="p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/20 hover:border-indigo-400 dark:hover:border-indigo-500 transition">
                                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <h4 class="font-bold text-base text-gray-900 dark:text-gray-100">
                                                        {{ $test->title }}
                                                    </h4>
                                                    @if (!$pivot->is_active)
                                                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200">
                                                            Inactive
                                                        </span>
                                                    @elseif ($pivot->starts_at && now()->lt($pivot->starts_at))
                                                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                                            Scheduled
                                                        </span>
                                                    @elseif ($pivot->due_at && now()->gt($pivot->due_at))
                                                        @if ($pivot->allow_late_submissions)
                                                            <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                                                Past Deadline (Late Allowed)
                                                            </span>
                                                        @else
                                                            <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                                                Closed
                                                            </span>
                                                        @endif
                                                    @else
                                                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                                            Active / Open
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400 mt-2">
                                                    <span><strong>{{ $test->duration_minutes }}</strong> mins</span>
                                                    <span>&bull;</span>
                                                    <span><strong>{{ $test->total_marks }}</strong> total marks</span>
                                                    <span>&bull;</span>
                                                    <span><strong>{{ $test->questions->count() }}</strong> questions ({{ $test->mcqCount() }} MCQ, {{ $test->textCount() }} Written)</span>
                                                </div>

                                                <!-- Availability Details -->
                                                <div class="mt-3 text-xs text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 p-2.5 rounded-lg border border-gray-100 dark:border-gray-700 space-y-1">
                                                    <div>
                                                        <span class="text-gray-500 dark:text-gray-400">Available From:</span>
                                                        <strong>{{ $pivot->starts_at ? \Carbon\Carbon::parse($pivot->starts_at)->format('M d, Y h:i A') : 'Immediately' }}</strong>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-500 dark:text-gray-400">Due / Closing:</span>
                                                        <strong>{{ $pivot->due_at ? \Carbon\Carbon::parse($pivot->due_at)->format('M d, Y h:i A') : 'No deadline' }}</strong>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-500 dark:text-gray-400">Late Submissions:</span>
                                                        <strong class="{{ $pivot->allow_late_submissions ? 'text-amber-600 dark:text-amber-400' : 'text-gray-600 dark:text-gray-400' }}">
                                                            {{ $pivot->allow_late_submissions ? 'Allowed (flagged as late)' : 'Not permitted' }}
                                                        </strong>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex flex-col items-end gap-2 shrink-0">
                                                <a href="{{ route('teacher.classrooms.tests.results', [$classroom, $test]) }}" class="inline-flex items-center px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                                                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                    </svg>
                                                    Results ({{ $submissions }})
                                                </a>

                                                <form method="POST" action="{{ route('teacher.classrooms.tests.unassign', [$classroom, $test]) }}" onsubmit="return confirm('Are you sure you want to remove this test assignment from the classroom?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300 font-semibold transition">
                                                        Unassign Test
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl p-8 text-center">
                                <svg class="w-10 h-10 mx-auto text-gray-400 dark:text-gray-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">No tests assigned to this classroom yet.</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Use the '+ Create Examination' button above to create and assign an examination to this classroom.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Google Classroom-Style Collapsible Roster & Requests Management -->
                    <div class="space-y-4">
                        <!-- Toggle Buttons Control Bar -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <!-- Enrolled Students Toggle Button -->
                            <button
                                type="button"
                                id="btnToggleStudents"
                                onclick="toggleClassroomPanel('students')"
                                class="w-full flex items-center justify-between p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:border-indigo-400 dark:hover:border-indigo-500 hover:shadow transition group text-left"
                                aria-expanded="false"
                                aria-controls="enrolledStudentsPanel"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="truncate">
                                        <div class="font-bold text-gray-900 dark:text-gray-100 text-sm group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                                            Enrolled Students ({{ $classroom->students->count() }})
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            View student roster & manage enrollment
                                        </p>
                                    </div>
                                </div>
                                <div class="shrink-0 ml-2">
                                    <svg id="studentsChevron" class="w-5 h-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-200 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </button>

                            <!-- Access Requests Toggle Button -->
                            <button
                                type="button"
                                id="btnToggleRequests"
                                onclick="toggleClassroomPanel('requests')"
                                class="w-full flex items-center justify-between p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:border-indigo-400 dark:hover:border-indigo-500 hover:shadow transition group text-left"
                                aria-expanded="false"
                                aria-controls="accessRequestsPanel"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                        </svg>
                                    </div>
                                    <div class="truncate">
                                        <div class="font-bold text-gray-900 dark:text-gray-100 text-sm group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition flex items-center gap-1.5">
                                            <span>Access Requests ({{ $classroom->joinRequests->count() }})</span>
                                            @if ($classroom->joinRequests->count() > 0)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                                    Action Required
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            Pending domain access approval queue
                                        </p>
                                    </div>
                                </div>
                                <div class="shrink-0 ml-2">
                                    <svg id="requestsChevron" class="w-5 h-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-200 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </button>
                        </div>

                        <!-- Enrolled Students Panel (Collapsed by default) -->
                        <div id="enrolledStudentsPanel" class="hidden bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700 transition">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                        Enrolled Students ({{ $classroom->students->count() }})
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Students currently enrolled in this classroom (join code: <strong class="font-mono text-indigo-600 dark:text-indigo-400">{{ $classroom->code }}</strong>).
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    onclick="toggleClassroomPanel('students')"
                                    class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 font-medium px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                >
                                    Close Panel &times;
                                </button>
                            </div>

                            @if ($classroom->students->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs uppercase text-gray-500 dark:text-gray-400 font-semibold border-b border-gray-100 dark:border-gray-700">
                                            <tr>
                                                <th class="py-3 px-4">#</th>
                                                <th class="py-3 px-4">Student Name</th>
                                                <th class="py-3 px-4">Email</th>
                                                <th class="py-3 px-4">Enrolled On</th>
                                                <th class="py-3 px-4 text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                            @foreach ($classroom->students as $idx => $student)
                                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                                    <td class="py-3 px-4 text-xs font-mono text-gray-400">{{ $idx + 1 }}</td>
                                                    <td class="py-3 px-4 font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                                        <div class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 flex items-center justify-center font-bold text-xs">
                                                            {{ strtoupper(substr($student->name, 0, 1)) }}
                                                        </div>
                                                        {{ $student->name }}
                                                    </td>
                                                    <td class="py-3 px-4 text-xs">{{ $student->email }}</td>
                                                    <td class="py-3 px-4 text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $student->pivot->created_at ? $student->pivot->created_at->format('M d, Y h:i A') : 'N/A' }}
                                                    </td>
                                                    <td class="py-3 px-4 text-right">
                                                        <form
                                                            method="POST"
                                                            action="{{ route('teacher.classrooms.students.remove', [$classroom, $student]) }}"
                                                            onsubmit="return confirm('Are you sure you want to remove {{ addslashes($student->name) }} from this classroom? Their exam attempts and records will be preserved.');"
                                                            class="inline"
                                                        >
                                                            @csrf
                                                            @method('DELETE')
                                                            <button
                                                                type="submit"
                                                                title="Remove student from classroom"
                                                                class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-md text-rose-700 bg-rose-50 hover:bg-rose-100 dark:text-rose-300 dark:bg-rose-900/30 dark:hover:bg-rose-900/50 border border-rose-200 dark:border-rose-800 transition"
                                                            >
                                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                </svg>
                                                                Remove
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl p-8 text-center">
                                    <div class="w-12 h-12 mx-auto mb-2 text-gray-400 dark:text-gray-500 flex items-center justify-center">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">No students enrolled yet.</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Instruct students to sign in and join using code: <strong class="font-mono text-indigo-600 dark:text-indigo-400">{{ $classroom->code }}</strong>
                                    </p>
                                </div>
                            @endif
                        </div>

                        <!-- Access Requests Panel (Collapsed by default, active queue) -->
                        <div id="accessRequestsPanel" class="hidden bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700 transition">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                        <span>Access Requests ({{ $classroom->joinRequests->count() }})</span>
                                        @if ($classroom->joinRequests->count() > 0)
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                                {{ $classroom->joinRequests->count() }} Pending Review
                                            </span>
                                        @endif
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        Active pending requests from students whose email addresses do not match the classroom domain restriction.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    onclick="toggleClassroomPanel('requests')"
                                    class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 font-medium px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                >
                                    Close Panel &times;
                                </button>
                            </div>

                            @if ($classroom->joinRequests->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs uppercase text-gray-500 dark:text-gray-400 font-semibold border-b border-gray-100 dark:border-gray-700">
                                            <tr>
                                                <th class="py-3 px-4">Student</th>
                                                <th class="py-3 px-4">Registered Email</th>
                                                <th class="py-3 px-4">Requested Date</th>
                                                <th class="py-3 px-4">Status</th>
                                                <th class="py-3 px-4 text-right">Review Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                            @foreach ($classroom->joinRequests as $requestItem)
                                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                                    <td class="py-3 px-4 font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                                        <div class="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-bold text-xs">
                                                            {{ strtoupper(substr($requestItem->user->name, 0, 1)) }}
                                                        </div>
                                                        {{ $requestItem->user->name }}
                                                    </td>
                                                    <td class="py-3 px-4 text-xs font-mono text-gray-700 dark:text-gray-300">
                                                        {{ $requestItem->user->email }}
                                                    </td>
                                                    <td class="py-3 px-4 text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $requestItem->created_at->format('M d, Y h:i A') }}
                                                    </td>
                                                    <td class="py-3 px-4 text-xs">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                                            Pending
                                                        </span>
                                                    </td>
                                                    <td class="py-3 px-4 text-right">
                                                        <div class="flex items-center justify-end gap-1.5">
                                                            <!-- Accept Button -->
                                                            <form method="POST" action="{{ route('teacher.classrooms.join-requests.update', [$classroom, $requestItem]) }}" class="inline">
                                                                @csrf
                                                                <input type="hidden" name="status" value="accepted" />
                                                                <button
                                                                    type="submit"
                                                                    title="Accept student into classroom"
                                                                    class="px-2.5 py-1 text-xs font-semibold rounded-md shadow-sm transition bg-emerald-600 hover:bg-emerald-700 text-white"
                                                                >
                                                                    Accept
                                                                </button>
                                                            </form>

                                                            <!-- Reject Button -->
                                                            <form method="POST" action="{{ route('teacher.classrooms.join-requests.update', [$classroom, $requestItem]) }}" class="inline">
                                                                @csrf
                                                                <input type="hidden" name="status" value="rejected" />
                                                                <button
                                                                    type="submit"
                                                                    title="Reject request and deny access"
                                                                    class="px-2.5 py-1 text-xs font-semibold rounded-md shadow-sm transition bg-rose-600 hover:bg-rose-700 text-white"
                                                                >
                                                                    Reject
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl p-8 text-center">
                                    <div class="w-12 h-12 mx-auto mb-2 text-gray-400 dark:text-gray-500 flex items-center justify-center">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">No pending access requests.</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Requests awaiting review will appear here automatically.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

            </div>

        </div>
    </div>

    <script>
        function toggleClassroomPanel(target) {
            const studentsPanel = document.getElementById('enrolledStudentsPanel');
            const requestsPanel = document.getElementById('accessRequestsPanel');
            const studentsBtn = document.getElementById('btnToggleStudents');
            const requestsBtn = document.getElementById('btnToggleRequests');
            const studentsChevron = document.getElementById('studentsChevron');
            const requestsChevron = document.getElementById('requestsChevron');

            if (target === 'students') {
                const isCurrentlyOpen = !studentsPanel.classList.contains('hidden');
                if (isCurrentlyOpen) {
                    // Collapse students panel
                    studentsPanel.classList.add('hidden');
                    studentsBtn.setAttribute('aria-expanded', 'false');
                    if (studentsChevron) studentsChevron.classList.remove('rotate-180');
                    studentsBtn.classList.remove('border-indigo-500', 'ring-2', 'ring-indigo-100', 'dark:ring-indigo-900/40');
                } else {
                    // Expand students panel, collapse requests panel (mutual exclusivity)
                    studentsPanel.classList.remove('hidden');
                    studentsBtn.setAttribute('aria-expanded', 'true');
                    if (studentsChevron) studentsChevron.classList.add('rotate-180');
                    studentsBtn.classList.add('border-indigo-500', 'ring-2', 'ring-indigo-100', 'dark:ring-indigo-900/40');

                    requestsPanel.classList.add('hidden');
                    requestsBtn.setAttribute('aria-expanded', 'false');
                    if (requestsChevron) requestsChevron.classList.remove('rotate-180');
                    requestsBtn.classList.remove('border-indigo-500', 'ring-2', 'ring-indigo-100', 'dark:ring-indigo-900/40');
                }
            } else if (target === 'requests') {
                const isCurrentlyOpen = !requestsPanel.classList.contains('hidden');
                if (isCurrentlyOpen) {
                    // Collapse requests panel
                    requestsPanel.classList.add('hidden');
                    requestsBtn.setAttribute('aria-expanded', 'false');
                    if (requestsChevron) requestsChevron.classList.remove('rotate-180');
                    requestsBtn.classList.remove('border-indigo-500', 'ring-2', 'ring-indigo-100', 'dark:ring-indigo-900/40');
                } else {
                    // Expand requests panel, collapse students panel (mutual exclusivity)
                    requestsPanel.classList.remove('hidden');
                    requestsBtn.setAttribute('aria-expanded', 'true');
                    if (requestsChevron) requestsChevron.classList.add('rotate-180');
                    requestsBtn.classList.add('border-indigo-500', 'ring-2', 'ring-indigo-100', 'dark:ring-indigo-900/40');

                    studentsPanel.classList.add('hidden');
                    studentsBtn.setAttribute('aria-expanded', 'false');
                    if (studentsChevron) studentsChevron.classList.remove('rotate-180');
                    studentsBtn.classList.remove('border-indigo-500', 'ring-2', 'ring-indigo-100', 'dark:ring-indigo-900/40');
                }
            }
        }
    </script>
</x-app-layout>
