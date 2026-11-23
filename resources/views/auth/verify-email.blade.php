<x-guest-layout>
    <!-- Sage Green Academic Header Badge -->
    <div class="bg-[#a3cbbe] rounded-3xl p-5 mb-4 shadow-md flex items-center justify-between border border-[#8ebaa9]/40">
        <div>
            <span class="text-[11px] uppercase tracking-wider font-bold text-[#143d2d] block mb-0.5">Account Security</span>
            <h2 class="text-lg font-bold text-[#103024] leading-tight">Verify Your Email Address</h2>
        </div>
        <div class="w-12 h-12 bg-white/40 backdrop-blur-sm rounded-2xl flex items-center justify-center text-2xl shadow-inner shrink-0 ml-3">
            🎓
        </div>
    </div>

    <!-- White Verification Card -->
    <div class="bg-white rounded-3xl shadow-2xl p-6 sm:p-8">
        <div class="flex items-center justify-center w-14 h-14 bg-emerald-50 text-[#164e3b] rounded-2xl mx-auto mb-4 border border-emerald-100">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </div>

        <div class="text-center mb-6">
            <h1 class="text-xl font-bold text-gray-900 tracking-tight">Check Your Inbox</h1>
            <p class="text-xs sm:text-sm text-gray-600 mt-2 leading-relaxed">
                Thanks for joining SecureTest! To maintain test integrity and protect your examinations, please verify your email address by clicking the link we sent to:
            </p>
            <div class="mt-2 inline-block px-3 py-1 bg-gray-100 rounded-lg text-xs font-semibold text-gray-800 font-mono">
                {{ auth()->user()->email ?? 'your email' }}
            </div>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs sm:text-sm text-emerald-800 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>A new verification link has been sent to your email address.</span>
            </div>
        @endif

        <div class="space-y-3 pt-2">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button
                    type="submit"
                    class="w-full py-3 px-4 bg-[#164e3b] hover:bg-[#123e2f] active:bg-[#0e3024] text-white font-semibold text-sm rounded-xl shadow-md transition duration-150 flex items-center justify-center cursor-pointer"
                >
                    Resend Verification Email
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    class="w-full py-2.5 px-4 border border-gray-200 hover:bg-gray-50 text-gray-600 font-medium text-xs sm:text-sm rounded-xl transition duration-150 flex items-center justify-center cursor-pointer"
                >
                    Sign Out
                </button>
            </form>
        </div>

        <div class="mt-6 pt-5 border-t border-gray-100 text-center">
            <p class="text-xs text-gray-400">
                Did not receive the email? Check your Spam or Junk folder, or click Resend.
            </p>
        </div>
    </div>
</x-guest-layout>
