<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                    {{ __('My Classrooms') }}
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Manage student classrooms, generate join codes, and assign examinations.
                </p>
            </div>
            <a href="{{ route('teacher.classrooms.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-sm transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create Classroom
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

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

            @if ($classrooms->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($classrooms as $classroom)
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 hover:border-indigo-500/50 transition flex flex-col justify-between overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-start justify-between gap-3 mb-2">
                                    <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100 line-clamp-1">
                                        <a href="{{ route('teacher.classrooms.show', $classroom) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                            {{ $classroom->name }}
                                        </a>
                                    </h3>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                        {{ $classroom->code }}
                                    </span>
                                </div>

                                @if ($classroom->description)
                                    <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2 mb-4">
                                        {{ $classroom->description }}
                                    </p>
                                @else
                                    <p class="text-xs italic text-gray-400 dark:text-gray-500 mb-4">
                                        No description provided.
                                    </p>
                                @endif

                                <div class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-100 dark:border-gray-700 text-center">
                                    <div class="p-2 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                                        <span class="block text-lg font-bold text-gray-800 dark:text-gray-200">{{ $classroom->students_count }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Enrolled Students</span>
                                    </div>
                                    <div class="p-2 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                                        <span class="block text-lg font-bold text-gray-800 dark:text-gray-200">{{ $classroom->tests_count }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Assigned Tests</span>
                                    </div>
                                </div>
                            </div>

                            <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/20 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between text-xs">
                                <div class="flex items-center gap-3">
                                    <span class="text-gray-500 dark:text-gray-400">
                                        {{ $classroom->created_at->diffForHumans() }}
                                    </span>
                                    <span class="text-gray-300 dark:text-gray-600">&bull;</span>
                                    <a href="{{ route('teacher.classrooms.edit', $classroom) }}" class="inline-flex items-center text-gray-600 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 font-semibold transition" title="Edit / Rename Classroom">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        Edit
                                    </a>
                                </div>
                                <a href="{{ route('teacher.classrooms.show', $classroom) }}" class="inline-flex items-center font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
                                    Manage Classroom &rarr;
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $classrooms->links() }}
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center border-2 border-dashed border-gray-200 dark:border-gray-700">
                    <div class="w-16 h-16 mx-auto mb-4 p-3 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-2xl flex items-center justify-center">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-1">
                        No Classrooms Created Yet
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-6">
                        Create a classroom to generate a student join code and assign proctored examinations to your students.
                    </p>
                    <a href="{{ route('teacher.classrooms.create') }}" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-md transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Create Your First Classroom
                    </a>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
