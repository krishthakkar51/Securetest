<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-2xl text-stone-800 dark:text-stone-100 leading-tight">
                {{ __('Dashboard') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-6 w-full px-4 sm:px-6 lg:px-8"
         x-data="{ sidebarOpen: localStorage.getItem('securetest_sidebar_open') !== 'false' }"
         x-init="$watch('sidebarOpen', val => localStorage.setItem('securetest_sidebar_open', val))">

        <!-- Flash Alerts -->
        @if (session('success'))
            <div class="mb-6 p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl text-emerald-800 dark:text-emerald-300 text-sm flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 text-emerald-600 dark:text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if (session('info'))
            <div class="mb-6 p-4 bg-stone-100 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 rounded-xl text-stone-800 dark:text-stone-200 text-sm flex items-center">
                <svg class="w-5 h-5 mr-3 text-stone-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                <span>{{ session('info') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-xl text-rose-800 dark:text-rose-300 text-sm flex items-center">
                <svg class="w-5 h-5 mr-3 text-rose-600 dark:text-rose-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Google Classroom-Style 2-Column Layout -->
        <div class="flex flex-col lg:flex-row gap-6 items-start">

            <!-- Left Sidebar: Compact vertical list of classrooms, scrolls independently -->
            <aside
                x-show="sidebarOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-x-4"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 -translate-x-4"
                class="w-full lg:w-72 shrink-0 bg-stone-50 dark:bg-stone-900 border border-stone-200 dark:border-stone-800 rounded-2xl p-4 shadow-sm lg:sticky lg:top-20 h-auto lg:h-[calc(100vh-6rem)] flex flex-col"
            >
                <div class="flex items-center justify-between pb-3 border-b border-stone-200 dark:border-stone-800 shrink-0">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-stone-600 dark:text-stone-400">Classrooms</span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-stone-200 dark:bg-stone-800 font-bold text-stone-700 dark:text-stone-300">
                            {{ $sidebarClassrooms->count() }}
                        </span>
                    </div>
                    <button
                        @click="sidebarOpen = false"
                        type="button"
                        class="p-1 rounded-lg text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-200 dark:hover:bg-stone-800 transition"
                        title="Collapse sidebar"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                        </svg>
                    </button>
                </div>

                <!-- Independently scrollable classroom list -->
                <div class="mt-3 overflow-y-auto flex-1 space-y-2 pr-1">
                    @forelse ($sidebarClassrooms as $sc)
                        @php
                            $isOwner = $sc->user_id === Auth::id();
                            $targetUrl = $isOwner ? route('teacher.classrooms.show', $sc) : route('student.classrooms.show', $sc);
                        @endphp
                        <a href="{{ $targetUrl }}" class="group flex items-center justify-between gap-2.5 p-3 rounded-xl bg-white dark:bg-stone-800/90 border border-stone-200 dark:border-stone-700/80 shadow-xs hover:border-stone-300 dark:hover:border-stone-600 hover:bg-stone-100/60 dark:hover:bg-stone-800 transition">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-stone-900 dark:text-stone-100 truncate group-hover:text-amber-800 dark:group-hover:text-amber-300 leading-snug" title="{{ $sc->name }}">
                                    {{ $sc->name }}
                                </p>
                                <p class="text-[10px] text-stone-500 truncate mt-0.5">
                                    {{ $isOwner ? 'Teaching' : ($sc->teacher ? $sc->teacher->name : 'Enrolled') }}
                                </p>
                            </div>
                            @if ($isOwner)
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 shrink-0">
                                    Owner
                                </span>
                            @endif
                        </a>
                    @empty
                        <div class="text-center py-6 text-stone-400 text-xs">
                            No classrooms yet
                        </div>
                    @endforelse
                </div>
            </aside>

            <!-- Main Area -->
            <main class="flex-1 min-w-0 space-y-6 w-full">

                <!-- Expand Sidebar Button (Shown when sidebar is collapsed) -->
                <div x-show="!sidebarOpen" x-cloak class="mb-2">
                    <button
                        @click="sidebarOpen = true"
                        type="button"
                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-stone-800 hover:bg-stone-100 dark:hover:bg-stone-700 text-stone-700 dark:text-stone-300 text-xs font-semibold rounded-xl border border-stone-200 dark:border-stone-700 shadow-xs transition"
                        title="Show Classrooms"
                    >
                        <svg class="w-4 h-4 text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                        </svg>
                        <span>Show Classrooms</span>
                    </button>
                </div>

                <!-- Welcome Card -->
                <div class="bg-gradient-to-r from-stone-800 via-stone-900 to-stone-950 rounded-2xl shadow-md p-6 text-stone-100"
                     x-data="{ showJoinInput: {{ (session('error') || $errors->has('code')) ? 'true' : 'false' }}, joinCode: '{{ old('code') }}' }"
                     @keydown.escape="showJoinInput = false">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-bold">Welcome back, {{ Auth::user()->name }}!</h1>
                            <p class="text-stone-300 mt-1 text-sm">
                                Create classrooms, upload study materials, author secure examinations, and monitor student integrity.
                            </p>
                        </div>

                        <!-- Banner Actions: [ Enter class code ] [ Join Class ] [ + Create Class ] -->
                        <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 shrink-0">
                            <!-- Join Class Form -->
                            <form method="POST" action="{{ route('student.classrooms.join') }}" class="flex items-center gap-2">
                                @csrf

                                <!-- Compact Enter class code Input (Displayed only when showJoinInput is true) -->
                                <div x-show="showJoinInput" x-cloak class="relative flex items-center">
                                    <input
                                        x-ref="codeInput"
                                        type="text"
                                        name="code"
                                        x-model="joinCode"
                                        :required="showJoinInput"
                                        maxlength="20"
                                        placeholder="Enter class code"
                                        class="w-36 sm:w-40 pr-7 pl-3 py-2 rounded-xl bg-stone-700/80 border border-stone-600 text-stone-100 placeholder-stone-400 text-xs font-mono uppercase tracking-wider focus:outline-none focus:ring-1 focus:ring-stone-400 focus:border-stone-400 transition"
                                    />
                                    <button
                                        type="button"
                                        @click="showJoinInput = false; joinCode = ''"
                                        class="absolute right-2 text-stone-400 hover:text-stone-200 transition"
                                        title="Cancel"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>

                                <!-- Join Class Button: toggles input when closed, submits form when open -->
                                <button
                                    type="button"
                                    x-show="!showJoinInput"
                                    @click="showJoinInput = true; $nextTick(() => $refs.codeInput.focus())"
                                    class="inline-flex items-center px-4 py-2 bg-stone-700/80 hover:bg-stone-700 text-stone-100 rounded-xl font-bold text-xs transition border border-stone-600 shadow-sm"
                                    title="Join Class"
                                >
                                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                    Join Class
                                </button>

                                <button
                                    type="submit"
                                    x-show="showJoinInput"
                                    x-cloak
                                    class="inline-flex items-center px-4 py-2 bg-stone-700/80 hover:bg-stone-700 text-stone-100 rounded-xl font-bold text-xs transition border border-stone-600 shadow-sm"
                                    title="Submit Join Code"
                                >
                                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                    Join Class
                                </button>
                            </form>

                            <!-- + Create Class Button -->
                            <a href="{{ route('teacher.classrooms.create') }}" class="inline-flex items-center px-4 py-2 bg-stone-100 text-stone-900 hover:bg-white rounded-xl font-bold text-xs shadow transition whitespace-nowrap">
                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                + Create Class
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Upcoming & Due Work (If enrolled in any classrooms) -->
                @if ($upcomingWork->count() > 0)
                    <section class="bg-stone-50/70 dark:bg-stone-900/60 border border-stone-200 dark:border-stone-800 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-2.5 h-2.5 rounded-full bg-amber-500"></div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                    Upcoming &amp; Due Work
                                </h3>
                            </div>
                            <span class="text-xs text-stone-500">
                                {{ $upcomingWork->count() }} task(s)
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach ($upcomingWork->take(4) as $assignment)
                                @php
                                    $isPast = $assignment->isPastDeadline();
                                    $isUpcoming = $assignment->isUpcoming();
                                @endphp
                                <div class="bg-white dark:bg-stone-800 border border-stone-200 dark:border-stone-700 rounded-xl p-3.5 flex flex-col justify-between hover:border-stone-400 transition">
                                    <div>
                                        <div class="flex items-start justify-between gap-2">
                                            <span class="text-[11px] font-semibold text-stone-500 dark:text-stone-400 truncate">
                                                {{ $assignment->classroom->name }}
                                            </span>
                                            @if ($isPast)
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">Past Due</span>
                                            @elseif ($isUpcoming)
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300">Scheduled</span>
                                            @else
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Active</span>
                                            @endif
                                        </div>
                                        <h4 class="font-bold text-sm text-stone-900 dark:text-stone-100 mt-1">
                                            {{ $assignment->test->title }}
                                        </h4>
                                    </div>
                                    <div class="mt-3 pt-2.5 border-t border-stone-100 dark:border-stone-700/60 flex items-center justify-between text-xs">
                                        <span class="text-stone-500">
                                            @if ($assignment->due_at)
                                                Due {{ $assignment->due_at->diffForHumans() }}
                                            @else
                                                No deadline
                                            @endif
                                        </span>
                                        <a href="{{ route('student.classrooms.tests.show', [$assignment->classroom, $assignment->test]) }}" class="font-bold text-stone-800 dark:text-stone-200 hover:text-amber-700">
                                            Open Exam &rarr;
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <!-- Classroom Cards: 3 Per Row on Desktop, Enrolled First, Then Managed -->
                <section>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-stone-900 dark:text-stone-100">
                                Classrooms
                            </h3>
                            <p class="text-xs text-stone-500">
                                Enrolled classrooms and teaching spaces
                            </p>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300">
                            {{ $enrolledClassrooms->count() + $managedCards->count() }} Total
                        </span>
                    </div>

                    @php
                        $totalCards = $enrolledClassrooms->count() + $managedCards->count();
                    @endphp

                    @if ($totalCards === 0)
                        <!-- Clean Empty State -->
                        <div class="bg-stone-50 dark:bg-stone-900 border-2 border-dashed border-stone-300 dark:border-stone-700 rounded-2xl p-10 text-center">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-stone-200 dark:bg-stone-800 flex items-center justify-center text-stone-600 dark:text-stone-400">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <h4 class="text-base font-bold text-stone-900 dark:text-stone-100">No classrooms yet</h4>
                            <p class="text-xs text-stone-500 max-w-sm mx-auto mt-1 mb-6">
                                Get started by creating your first classroom or joining an existing classroom.
                            </p>
                            <div class="flex items-center justify-center gap-3">
                                <a href="{{ route('teacher.classrooms.create') }}" class="px-4 py-2 bg-stone-900 hover:bg-black text-stone-100 dark:bg-stone-100 dark:hover:bg-white dark:text-stone-900 font-bold text-xs rounded-xl shadow transition">
                                    + Create Class
                                </a>
                                <button type="button" @click="window.scrollTo({top: 0, behavior: 'smooth'}); showJoinInput = true; $nextTick(() => $refs.codeInput.focus())" class="px-4 py-2 border border-stone-300 dark:border-stone-600 text-stone-700 dark:text-stone-300 font-bold text-xs rounded-xl hover:bg-stone-100 dark:hover:bg-stone-800 transition">
                                    Join Class
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Enrolled Classrooms First -->
                            @foreach ($enrolledClassrooms as $c)
                                @php
                                    $theme = $c->bannerTheme();
                                    $bannerUrl = $c->bannerUrl();
                                @endphp
                                <a href="{{ route('student.classrooms.show', $c) }}" class="group bg-white dark:bg-stone-800 border border-stone-200 dark:border-stone-700/80 rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:border-stone-400 dark:hover:border-stone-600 transition flex flex-col justify-between block focus:outline-none focus:ring-2 focus:ring-stone-500">
                                    <!-- Wide Banner Header -->
                                    <div class="h-28 relative p-4 flex flex-col justify-between" style="{{ $bannerUrl ? "background-image: url('{$bannerUrl}'); background-size: cover; background-position: center;" : "background: {$theme['bg']};" }}">
                                        <div class="absolute inset-0 bg-black/25"></div>
                                        <div class="relative z-10 flex items-start justify-between">
                                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-white/20 text-white backdrop-blur-sm">
                                                Enrolled
                                            </span>
                                            <span class="text-[11px] font-medium text-white/90">
                                                {{ $c->teacher ? $c->teacher->name : 'Instructor' }}
                                            </span>
                                        </div>
                                        <div class="relative z-10">
                                            <h4 class="font-bold text-base text-white line-clamp-1 drop-shadow-sm group-hover:underline">
                                                {{ $c->name }}
                                            </h4>
                                        </div>
                                    </div>

                                    <!-- Card Content -->
                                    <div class="p-4 flex-1 flex flex-col justify-between">
                                        <p class="text-xs text-stone-600 dark:text-stone-300 line-clamp-2 min-h-[2rem]">
                                            {{ $c->description ?: 'No description provided.' }}
                                        </p>
                                        <div class="mt-4 pt-3 border-t border-stone-100 dark:border-stone-700 flex items-center justify-between text-xs">
                                            <span class="text-stone-500 font-medium">
                                                {{ $c->tests_count }} Test(s)
                                            </span>
                                            <svg class="w-4 h-4 text-stone-400 group-hover:text-stone-600 dark:group-hover:text-stone-200 group-hover:translate-x-0.5 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </div>
                                    </div>
                                </a>
                            @endforeach

                            <!-- Managed Classrooms Afterward -->
                            @foreach ($managedCards as $c)
                                @php
                                    $theme = $c->bannerTheme();
                                    $bannerUrl = $c->bannerUrl();
                                @endphp
                                <a href="{{ route('teacher.classrooms.show', $c) }}" class="group bg-white dark:bg-stone-800 border border-stone-200 dark:border-stone-700/80 rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:border-stone-400 dark:hover:border-stone-600 transition flex flex-col justify-between block focus:outline-none focus:ring-2 focus:ring-stone-500">
                                    <!-- Wide Banner Header -->
                                    <div class="h-28 relative p-4 flex flex-col justify-between" style="{{ $bannerUrl ? "background-image: url('{$bannerUrl}'); background-size: cover; background-position: center;" : "background: {$theme['bg']};" }}">
                                        <div class="absolute inset-0 bg-black/25"></div>
                                        <div class="relative z-10 flex items-start justify-between">
                                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-amber-500/80 text-white backdrop-blur-sm">
                                                Teaching
                                            </span>
                                            <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-black/40 text-white backdrop-blur-sm" title="Classroom Join Code">
                                                Code: {{ $c->code }}
                                            </span>
                                        </div>
                                        <div class="relative z-10">
                                            <h4 class="font-bold text-base text-white line-clamp-1 drop-shadow-sm group-hover:underline">
                                                {{ $c->name }}
                                            </h4>
                                        </div>
                                    </div>

                                    <!-- Card Content -->
                                    <div class="p-4 flex-1 flex flex-col justify-between">
                                        <p class="text-xs text-stone-600 dark:text-stone-300 line-clamp-2 min-h-[2rem]">
                                            {{ $c->description ?: 'No description provided.' }}
                                        </p>
                                        <div class="mt-4 pt-3 border-t border-stone-100 dark:border-stone-700 flex items-center justify-between text-xs">
                                            <span class="text-stone-500 font-medium">
                                                {{ $c->students_count }} student(s) &bull; {{ $c->tests_count }} test(s)
                                            </span>
                                            <svg class="w-4 h-4 text-stone-400 group-hover:text-stone-600 dark:group-hover:text-stone-200 group-hover:translate-x-0.5 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>

            </main>
        </div>
    </div>
</x-app-layout>