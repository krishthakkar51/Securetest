<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                    {{ __('My Classrooms') }}
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Enter teacher join codes to enroll into classrooms and access proctored exams.
                </p>
            </div>
            <a href="{{ route('student.dashboard') }}" class="inline-flex items-center text-xs font-semibold px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg text-gray-700 dark:text-gray-200 transition">
                &larr; Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

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

            <!-- Join Classroom Form Card -->
            <div class="bg-gradient-to-r from-teal-700 via-emerald-800 to-teal-900 rounded-2xl shadow-lg p-6 text-white">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <span class="inline-block px-2.5 py-0.5 rounded bg-white/10 text-emerald-200 text-xs font-semibold uppercase tracking-wider mb-2">
                            Enrollment
                        </span>
                        <h2 class="text-2xl font-bold">Join a Classroom</h2>
                        <p class="text-emerald-100 text-sm mt-1 max-w-xl">
                            Enter the unique 7-character join code provided by your instructor to enroll in their course and access assigned examinations.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('student.classrooms.join') }}" class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                        @csrf
                        <div class="w-full sm:w-auto">
                            <input
                                type="text"
                                name="code"
                                required
                                maxlength="15"
                                placeholder="e.g. 7-char code"
                                value="{{ old('code') }}"
                                class="w-full sm:w-48 px-4 py-2.5 rounded-xl bg-white/10 text-white placeholder-emerald-200/60 border border-white/20 focus:outline-none focus:ring-2 focus:ring-emerald-400 font-mono uppercase tracking-widest text-center text-base"
                            />
                        </div>
                        <button type="submit" class="w-full sm:w-auto px-5 py-2.5 bg-white text-emerald-900 hover:bg-emerald-50 rounded-xl font-bold text-sm shadow-md transition whitespace-nowrap">
                            Join Classroom
                        </button>
                    </form>
                </div>
                @if ($errors->has('code'))
                    <p class="text-xs text-rose-200 mt-2 font-medium">{{ $errors->first('code') }}</p>
                @endif
            </div>

            <!-- My Access Requests -->
            @if (isset($myJoinRequests) && $myJoinRequests->count() > 0)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                My Classroom Access Requests
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Track the status of your requests to join email-restricted classrooms.
                            </p>
                        </div>
                        <span class="text-xs text-gray-400 font-mono">
                            {{ $myJoinRequests->count() }} {{ \Illuminate\Support\Str::plural('Request', $myJoinRequests->count()) }}
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs uppercase text-gray-500 dark:text-gray-400 font-semibold border-b border-gray-100 dark:border-gray-700">
                                <tr>
                                    <th class="py-3 px-4">Classroom</th>
                                    <th class="py-3 px-4">Request Date</th>
                                    <th class="py-3 px-4 text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($myJoinRequests as $req)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                        <td class="py-3 px-4 font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $req->classroom->name }}
                                        </td>
                                        <td class="py-3 px-4 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $req->created_at->format('M d, Y h:i A') }}
                                        </td>
                                        <td class="py-3 px-4 text-xs text-right">
                                            @if ($req->isPending())
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                                    Pending
                                                </span>
                                            @elseif ($req->isAccepted())
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                                    Accepted
                                                </span>
                                            @elseif ($req->isRejected())
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                                    Rejected
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Enrolled Classrooms Grid -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                        Enrolled Classrooms ({{ $classrooms->count() }})
                    </h3>
                </div>

                @if ($classrooms->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($classrooms as $classroom)
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 hover:border-emerald-500/50 transition flex flex-col justify-between overflow-hidden">
                                <div class="p-6">
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <h4 class="font-bold text-lg text-gray-900 dark:text-gray-100 line-clamp-1">
                                            <a href="{{ route('student.classrooms.show', $classroom) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">
                                                {{ $classroom->name }}
                                            </a>
                                        </h4>
                                    </div>

                                    <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5 mb-3">
                                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        <strong class="text-gray-700 dark:text-gray-300">{{ $classroom->teacher->name }}</strong>
                                    </p>

                                    @if ($classroom->description)
                                        <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2 mb-4">
                                            {{ $classroom->description }}
                                        </p>
                                    @endif

                                    <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                        <span><strong>{{ $classroom->tests_count }}</strong> Assigned Tests</span>
                                        <span><strong>{{ $classroom->students_count }}</strong> Peers</span>
                                    </div>
                                </div>

                                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/20 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between text-xs">
                                    <span class="text-gray-400">
                                        Joined {{ $classroom->pivot->created_at ? $classroom->pivot->created_at->diffForHumans() : '' }}
                                    </span>
                                    <a href="{{ route('student.classrooms.show', $classroom) }}" class="inline-flex items-center font-semibold text-emerald-600 dark:text-emerald-400 hover:text-emerald-700">
                                        Enter Classroom &rarr;
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center border-2 border-dashed border-gray-200 dark:border-gray-700">
                        <div class="w-16 h-16 mx-auto mb-4 p-3 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-1">
                            You have not joined any classrooms yet
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-4">
                            Ask your instructor for their 7-character classroom join code and enter it in the form above to access your assigned tests.
                        </p>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
