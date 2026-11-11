<x-guest-layout>
    <div class="mb-7 text-center">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Masuk</h1>
        <p class="mt-1 text-sm text-slate-500">NALA IT Helpdesk</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="nama@kantor.com"
                class="w-full rounded-xl border-slate-300 bg-white px-4 py-3 text-sm shadow-sm transition focus:border-[#1E40AF] focus:ring-[#1E40AF]"
            >
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="mb-2 flex items-center justify-between gap-4">
                <label for="password" class="text-sm font-semibold text-slate-700">Kata sandi</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs font-semibold text-[#1E40AF] hover:underline">
                        Lupa sandi?
                    </a>
                @endif
            </div>

            <div class="relative" x-data="{ showPassword: false }">
                <input
                    id="password"
                    :type="showPassword ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Masukkan kata sandi"
                    class="w-full rounded-xl border-slate-300 bg-white px-4 py-3 pr-12 text-sm shadow-sm transition focus:border-[#1E40AF] focus:ring-[#1E40AF]"
                >
                <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-slate-400 transition hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[#1E40AF]"
                    :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                >
                    <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showPassword" style="display: none;" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="inline-flex cursor-pointer items-center gap-2">
            <input
                id="remember_me"
                type="checkbox"
                name="remember"
                class="rounded border-slate-300 text-[#1E40AF] shadow-sm focus:ring-[#1E40AF]"
            >
            <span class="text-sm text-slate-600">Ingat saya</span>
        </label>

        <button
            type="submit"
            class="flex w-full items-center justify-center rounded-xl bg-[#1E40AF] px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#1E3A8A] focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:ring-offset-2"
        >
            Masuk
        </button>
    </form>
</x-guest-layout>
