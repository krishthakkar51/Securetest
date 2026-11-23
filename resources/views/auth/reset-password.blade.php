<x-guest-layout>
    <!-- Sage Green Academic Header Badge -->
    <div class="bg-[#a3cbbe] rounded-3xl p-5 mb-4 shadow-md flex items-center justify-between border border-[#8ebaa9]/40">
        <div>
            <span class="text-[11px] uppercase tracking-wider font-bold text-[#143d2d] block mb-0.5">Password Recovery</span>
            <h2 class="text-lg font-bold text-[#103024] leading-tight">Create a New Password</h2>
        </div>
        <div class="w-12 h-12 bg-white/40 backdrop-blur-sm rounded-2xl flex items-center justify-center text-2xl shadow-inner shrink-0 ml-3">
            🎓
        </div>
    </div>

    <!-- White Form Card -->
    <div class="bg-white rounded-3xl shadow-2xl p-6 sm:p-8">
        <div class="mb-5">
            <h1 class="text-xl font-bold text-gray-900 tracking-tight">Set New Password</h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-1">Please enter your email and set your new password below</p>
        </div>

        <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
            @csrf

            <!-- Password Reset Token -->
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

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
                        value="{{ old('email', $request->email) }}"
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#164e3b] focus:border-transparent transition bg-gray-50/50"
                    />
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1.5">New Password</label>
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
                        placeholder="••••••••"
                        class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#164e3b] focus:border-transparent transition bg-gray-50/50"
                    />
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1.5">Confirm New Password</label>
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
                        placeholder="••••••••"
                        class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#164e3b] focus:border-transparent transition bg-gray-50/50"
                    />
                </div>
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                class="w-full mt-2 py-3 px-4 bg-[#164e3b] hover:bg-[#123e2f] active:bg-[#0e3024] text-white font-semibold text-sm rounded-xl shadow-md transition duration-150 flex items-center justify-center cursor-pointer"
            >
                Reset Password
            </button>
        </form>
    </div>
</x-guest-layout>
