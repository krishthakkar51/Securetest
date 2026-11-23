<x-guest-layout>
    <!-- Top Back to Sign In Link -->
    <div class="mb-3 flex items-center justify-between text-sm">
        <a href="{{ route('login') }}" class="inline-flex items-center text-[#a3cbbe] hover:text-white font-medium transition duration-150">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Sign In
        </a>
        <span class="text-[11px] bg-white/10 text-[#a3cbbe] px-2.5 py-0.5 rounded-full font-medium">New Student</span>
    </div>

    <!-- Sage Green Academic Header Badge -->
    <div class="bg-[#a3cbbe] rounded-3xl p-5 mb-4 shadow-md flex items-center justify-between border border-[#8ebaa9]/40">
        <div>
            <span class="text-[11px] uppercase tracking-wider font-bold text-[#143d2d] block mb-0.5">Secure Examination Platform</span>
            <h2 class="text-lg font-bold text-[#103024] leading-tight">Create your SecureTest Account</h2>
        </div>
        <div class="w-12 h-12 bg-white/40 backdrop-blur-sm rounded-2xl flex items-center justify-center text-2xl shadow-inner shrink-0 ml-3">
            🎓
        </div>
    </div>

    <!-- White Form Card -->
    <div class="bg-white rounded-3xl shadow-2xl p-6 sm:p-8">
        <div class="mb-5">
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Sign Up</h1>
            <p class="text-sm text-gray-500 mt-1">Get started with your classroom tests and assessments</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-3.5">
            @csrf

            <!-- Name -->
            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1.5">Full Name</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="John Doe"
                        class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#164e3b] focus:border-transparent transition bg-gray-50/50"
                    />
                </div>
                <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
            </div>

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1.5">Email Address</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                        </svg>
                    </div>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="username"
                        placeholder="name@example.com"
                        class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#164e3b] focus:border-transparent transition bg-gray-50/50"
                    />
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1.5">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        placeholder="Minimum 8 characters"
                        class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#164e3b] focus:border-transparent transition bg-gray-50/50"
                    />
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1.5">Confirm Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Re-enter password"
                        class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#164e3b] focus:border-transparent transition bg-gray-50/50"
                    />
                </div>
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
            </div>

            <!-- Sign Up Submit Button -->
            <button
                type="submit"
                class="w-full mt-2 py-3 px-4 bg-[#164e3b] hover:bg-[#123e2f] active:bg-[#0e3024] text-white font-semibold text-sm rounded-xl shadow-md transition duration-150 flex items-center justify-center cursor-pointer"
            >
                Sign Up
            </button>
        </form>

        <!-- Sign In Link -->
        <p class="text-center text-xs sm:text-sm text-gray-500 mt-6 pt-2">
            Already have an account?
            <a href="{{ route('login') }}" class="font-bold text-[#164e3b] hover:underline ml-1">
                Sign In
            </a>
        </p>
    </div>
</x-guest-layout>
