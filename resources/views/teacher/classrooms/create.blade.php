<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                    {{ __('Create New Classroom') }}
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Establish a classroom group with an auto-generated join code.
                </p>
            </div>
            <a href="{{ route('teacher.classrooms.index') }}" class="inline-flex items-center text-xs font-semibold px-3.5 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg text-gray-700 dark:text-gray-200 transition">
                &larr; Back to Classrooms
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-8 border border-gray-100 dark:border-gray-700">
                <form method="POST" action="{{ route('teacher.classrooms.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="name" :value="__('Classroom Name *')" />
                        <x-text-input
                            id="name"
                            name="name"
                            type="text"
                            class="mt-1 block w-full text-base"
                            :value="old('name')"
                            required
                            autofocus
                            placeholder="e.g. CS401 - Advanced Computer Networks (Fall 2026)"
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Choose a descriptive title for your course or cohort.
                        </p>
                    </div>

                    <div>
                        <x-input-label for="description" :value="__('Description (Optional)')" />
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm text-sm"
                            placeholder="Add syllabus details, schedule, or guidelines for enrolled students..."
                        >{{ old('description') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('description')" />
                    </div>

                    <!-- Classroom Banner Selection -->
                    <div x-data="{ selectedBanner: '{{ old('default_banner', '') }}' }">
                        <x-input-label :value="__('Classroom Banner (Optional)')" />
                        <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5 mb-2.5">
                            Select an academic banner theme, upload a custom image, or leave on Automatic to randomly assign an academic banner.
                        </p>

                        <!-- Academic Theme Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 mb-3">
                            <label class="relative rounded-xl overflow-hidden border-2 cursor-pointer transition p-1"
                                   :class="selectedBanner === '' ? 'border-emerald-600 ring-2 ring-emerald-500/30' : 'border-gray-200 dark:border-gray-700 hover:border-gray-400'">
                                <input type="radio" name="default_banner" value="" x-model="selectedBanner" class="sr-only">
                                <div class="h-12 rounded-lg bg-gradient-to-r from-emerald-800 to-teal-700 flex items-center justify-center text-center p-1">
                                    <span class="text-[11px] font-bold text-white flex items-center gap-1">
                                        🎲 Random Banner
                                    </span>
                                </div>
                                <span class="block text-[10px] text-center font-medium text-gray-500 mt-1">Automatic</span>
                            </label>

                            @php
                                $academicBanners = [
                                    ['path' => 'images/banners/math.svg', 'name' => 'Mathematics'],
                                    ['path' => 'images/banners/science.svg', 'name' => 'Natural Science'],
                                    ['path' => 'images/banners/technology.svg', 'name' => 'Tech & CS'],
                                    ['path' => 'images/banners/books.svg', 'name' => 'Literature & Arts'],
                                    ['path' => 'images/banners/education.svg', 'name' => 'General Education'],
                                    ['path' => 'images/banners/abstract.svg', 'name' => 'Abstract Pattern'],
                                ];
                            @endphp

                            @foreach ($academicBanners as $b)
                                <label class="relative rounded-xl overflow-hidden border-2 cursor-pointer transition p-1"
                                       :class="selectedBanner === '{{ $b['path'] }}' ? 'border-emerald-600 ring-2 ring-emerald-500/30' : 'border-gray-200 dark:border-gray-700 hover:border-gray-400'">
                                    <input type="radio" name="default_banner" value="{{ $b['path'] }}" x-model="selectedBanner" class="sr-only">
                                    <div class="h-12 rounded-lg overflow-hidden bg-cover bg-center border border-black/10" style="background-image: url('{{ asset($b['path']) }}');"></div>
                                    <span class="block text-[10px] text-center font-medium text-gray-600 dark:text-gray-300 mt-1 truncate">{{ $b['name'] }}</span>
                                </label>
                            @endforeach
                        </div>

                        <!-- Or Upload Custom File -->
                        <div class="pt-2 border-t border-gray-100 dark:border-gray-700">
                            <label for="banner_image" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Or Upload Custom Banner (JPG, PNG, WEBP - Max 3MB)
                            </label>
                            <input
                                id="banner_image"
                                name="banner_image"
                                type="file"
                                accept="image/png,image/jpeg,image/webp"
                                class="mt-1 block w-full text-xs text-stone-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-stone-100 file:text-stone-700 hover:file:bg-stone-200 dark:file:bg-stone-700 dark:file:text-stone-300 transition"
                            />
                            <x-input-error class="mt-1" :messages="$errors->get('banner_image')" />
                        </div>
                    </div>

                    <!-- Email Domain Restriction Settings -->
                    <div class="p-5 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700 space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="flex items-center h-5 mt-0.5">
                                <input
                                    id="is_email_restricted"
                                    name="is_email_restricted"
                                    type="checkbox"
                                    value="1"
                                    {{ old('is_email_restricted') ? 'checked' : '' }}
                                    onchange="document.getElementById('domainInputContainer').style.display = this.checked ? 'block' : 'none';"
                                    class="w-4 h-4 rounded text-indigo-600 border-gray-300 dark:border-gray-700 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label for="is_email_restricted" class="font-semibold text-sm text-gray-900 dark:text-gray-100 cursor-pointer">
                                    {{ __('Restrict Joining by Email Domain') }}
                                </label>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    Only students with matching registered email domains can join directly. Other students must request permission.
                                </p>
                            </div>
                        </div>

                        <div id="domainInputContainer" style="{{ old('is_email_restricted') ? 'display: block;' : 'display: none;' }}" class="pt-2 border-t border-gray-200 dark:border-gray-700">
                            <x-input-label for="allowed_email_domain" :value="__('Allowed Email Domain / Format')" />
                            <x-text-input
                                id="allowed_email_domain"
                                name="allowed_email_domain"
                                type="text"
                                class="mt-1 block w-full text-sm font-mono"
                                :value="old('allowed_email_domain')"
                                placeholder="e.g. @college.edu or @example.com"
                            />
                            <x-input-error class="mt-1" :messages="$errors->get('allowed_email_domain')" />
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Enter an exact domain such as <code class="bg-gray-200 dark:bg-gray-800 px-1 py-0.5 rounded font-mono text-[11px]">@college.edu</code>. Non-matching domains will automatically create an access request for your review.
                            </p>
                        </div>
                    </div>

                    <div class="p-4 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl border border-indigo-100 dark:border-indigo-800 text-xs text-indigo-800 dark:text-indigo-300 flex items-start gap-3">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <span class="font-bold">Automatic Join Code Generation</span>
                            <p class="mt-0.5 text-indigo-700/80 dark:text-indigo-300/80">
                                When this classroom is created, a unique 7-character join code will be generated. You can share this code with students so they can enroll.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <a href="{{ route('teacher.classrooms.index') }}" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-sm transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Create Classroom
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
