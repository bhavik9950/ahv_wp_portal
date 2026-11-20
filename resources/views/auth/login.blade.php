<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-base-content tracking-tight">Welcome back</h1>
        <p class="text-xs text-base-content/60 mt-1">Sign in with your administrator-provisioned credentials</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($errors->any())
        <div class="alert alert-error text-xs mb-4 py-2.5 px-3 rounded-lg shadow-sm">
            <i class="ti ti-alert-circle text-base shrink-0"></i>
            <div>
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div class="form-control">
            <label for="email" class="label pb-1">
                <span class="label-text font-medium text-xs text-base-content/80">Email address</span>
            </label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-base-content/40">
                    <i class="ti ti-mail text-lg"></i>
                </span>
                <input id="email"
                       type="email"
                       name="email"
                       value="{{ old('email') }}"
                       required
                       autofocus
                       autocomplete="username"
                       placeholder="name@company.com"
                       class="input input-bordered w-full pl-10 text-sm focus:input-primary @error('email') input-error @enderror" />
            </div>
        </div>

        <!-- Password -->
        <div class="form-control">
            <div class="flex items-center justify-between pb-1">
                <label for="password" class="label-text font-medium text-xs text-base-content/80">Password</label>
                @if (Route::has('password.request'))
                    <a class="link link-hover text-xs text-primary font-medium" href="{{ route('password.request') }}">
                        Forgot password?
                    </a>
                @endif
            </div>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-base-content/40">
                    <i class="ti ti-lock text-lg"></i>
                </span>
                <input id="password"
                       type="password"
                       name="password"
                       required
                       autocomplete="current-password"
                       placeholder="••••••••"
                       class="input input-bordered w-full pl-10 pr-10 text-sm focus:input-primary @error('password') input-error @enderror" />
                <button type="button"
                        id="togglePasswordBtn"
                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-base-content/40 hover:text-base-content transition"
                        title="Show/hide password"
                        tabindex="-1">
                    <i id="togglePasswordIcon" class="ti ti-eye text-lg"></i>
                </button>
            </div>
        </div>

        <!-- Remember Me -->
        <div class="pt-1">
            <label for="remember_me" class="cursor-pointer inline-flex items-center gap-2">
                <input id="remember_me"
                       type="checkbox"
                       class="checkbox checkbox-sm checkbox-primary rounded"
                       name="remember"
                       {{ old('remember') ? 'checked' : '' }}>
                <span class="text-xs text-base-content/70">Remember this device</span>
            </label>
        </div>

        <div class="pt-2">
            <button type="submit" class="btn btn-primary w-full gap-2 shadow-sm font-semibold">
                <i class="ti ti-login text-base"></i>
                <span>Sign In to Dashboard</span>
            </button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btn = document.getElementById('togglePasswordBtn');
            const input = document.getElementById('password');
            const icon = document.getElementById('togglePasswordIcon');
            if (btn && input && icon) {
                btn.addEventListener('click', function () {
                    const isPassword = input.getAttribute('type') === 'password';
                    input.setAttribute('type', isPassword ? 'text' : 'password');
                    icon.className = isPassword ? 'ti ti-eye-off text-lg' : 'ti ti-eye text-lg';
                });
            }
        });
    </script>
</x-guest-layout>
