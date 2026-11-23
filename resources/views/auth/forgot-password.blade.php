<x-guest-layout>
    <!-- Top Back to Sign In Link -->
    <div class="mb-3 flex items-center justify-between text-sm">
        <a href="{{ route('login') }}" class="inline-flex items-center text-[#a3cbbe] hover:text-white font-medium transition duration-150">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Sign In
        </a>
    </div>

    <!-- Sage Green Academic Header Badge -->
    <div class="bg-[#a3cbbe] rounded-3xl p-5 mb-4 shadow-md flex items-center justify-between border border-[#8ebaa9]/40">
        <div>
            <span class="text-[11px] uppercase tracking-wider font-bold text-[#143d2d] block mb-0.5">Password Recovery</span>
            <h2 class="text-lg font-bold text-[#103024] leading-tight">Reset Your Password</h2>
        </div>
        <div class="w-12 h-12 bg-white/40 backdrop-blur-sm rounded-2xl flex items-center justify-center text-2xl shadow-inner shrink-0 ml-3">
            🎓
        </div>
    </div>

    <!-- White Form Card -->
    <div class="bg-white rounded-3xl shadow-2xl p-6 sm:p-8">
        <div class="mb-5">
            <h1 class="text-xl font-bold text-gray-900 tracking-tight">Forgot Password</h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-1 leading-relaxed">
                Enter your registered email address and we'll email you a secure link to choose a new password.
            </p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

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
                        autofocus
                        placeholder="name@example.com"
                        class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#164e3b] focus:border-transparent transition bg-gray-50/50"
                    />
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                class="w-full mt-2 py-3 px-4 bg-[#164e3b] hover:bg-[#123e2f] active:bg-[#0e3024] text-white font-semibold text-sm rounded-xl shadow-md transition duration-150 flex items-center justify-center cursor-pointer"
            >
                Email Password Reset Link
            </button>
        </form>

        <div class="mt-6 pt-5 border-t border-gray-100 text-center">
            <a href="{{ route('login') }}" class="text-xs sm:text-sm font-semibold text-[#164e3b] hover:underline">
                Return to Sign In
            </a>
        </div>
    </div>
</x-guest-layout>
