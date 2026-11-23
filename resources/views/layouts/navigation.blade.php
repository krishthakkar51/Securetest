<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <!-- Primary Navigation Menu -->
    <div class="w-full px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @if (Auth::user()->isTeacher())
                        <x-nav-link :href="route('teacher.dashboard')" :active="request()->routeIs('teacher.dashboard')">
                            {{ __('Dashboard') }}
                        </x-nav-link>
                        <x-nav-link :href="route('teacher.classrooms.index')" :active="request()->routeIs('teacher.classrooms.*')">
                            {{ __('Classrooms') }}
                        </x-nav-link>
                        <x-nav-link :href="route('teacher.tests.index')" :active="request()->routeIs('teacher.tests.index', 'teacher.tests.show')">
                            {{ __('My Tests') }}
                        </x-nav-link>
                    @else
                        <x-nav-link :href="route('student.dashboard')" :active="request()->routeIs('student.dashboard')">
                            {{ __('Dashboard') }}
                        </x-nav-link>
                        <x-nav-link :href="route('student.classrooms.index')" :active="request()->routeIs('student.classrooms.*')">
                            {{ __('Classrooms') }}
                        </x-nav-link>
                        <x-nav-link :href="route('test')" :active="request()->routeIs('test')">
                            {{ __('Proctoring Check') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Profile Avatar & Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 space-x-3">
                <!-- Google Classroom Plus (+) Menu -->
                <div class="relative flex items-center" x-data="{ plusDropdownOpen: false }">
                    <button @click="plusDropdownOpen = !plusDropdownOpen" @click.outside="plusDropdownOpen = false" type="button" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-stone-100 hover:bg-stone-200 dark:bg-stone-800 dark:hover:bg-stone-700 text-stone-700 dark:text-stone-300 font-bold transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-stone-500" title="Create or Join Classroom">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                    </button>
                    <div x-show="plusDropdownOpen" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 top-full mt-2 w-48 rounded-md shadow-lg py-1 bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 z-50 divide-y divide-gray-100 dark:divide-gray-700" style="display: none;">
                        <a href="{{ route('teacher.classrooms.create') }}" class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-amber-50 dark:hover:bg-gray-700 hover:text-amber-800 transition">
                            <svg class="w-4 h-4 mr-2 text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            {{ __('Create Classroom') }}
                        </a>
                        <a href="{{ route('student.classrooms.index') }}" class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-emerald-50 dark:hover:bg-gray-700 hover:text-emerald-800 transition">
                            <svg class="w-4 h-4 mr-2 text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            {{ __('Join Classroom') }}
                        </a>
                    </div>
                </div>

                <!-- Circular Profile Avatar linking directly to Profile -->
                <a href="{{ route('profile.edit') }}" class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-stone-700 hover:bg-stone-900 text-stone-100 font-semibold text-xs tracking-wider transition ring-2 ring-stone-300 dark:ring-stone-600 focus:outline-none" title="View Profile ({{ Auth::user()->name }})">
                    {{ Auth::user()->initials() }}
                </a>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-2 py-1.5 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                            <span class="max-w-[120px] truncate text-xs">{{ Auth::user()->name }}</span>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700">
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                        </div>

                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('teacher.classrooms.create')">
                {{ __('+ Create Classroom') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('student.classrooms.index')">
                {{ __('+ Join Classroom') }}
            </x-responsive-nav-link>

            @if (Auth::user()->isTeacher())
                <x-responsive-nav-link :href="route('teacher.dashboard')" :active="request()->routeIs('teacher.dashboard')">
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('teacher.classrooms.index')" :active="request()->routeIs('teacher.classrooms.*')">
                    {{ __('Classrooms') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('teacher.tests.index')" :active="request()->routeIs('teacher.tests.index', 'teacher.tests.show')">
                    {{ __('My Tests') }}
                </x-responsive-nav-link>
            @else
                <x-responsive-nav-link :href="route('student.dashboard')" :active="request()->routeIs('student.dashboard')">
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('student.classrooms.index')" :active="request()->routeIs('student.classrooms.*')">
                    {{ __('Classrooms') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('test')" :active="request()->routeIs('test')">
                    {{ __('Proctoring Check') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
