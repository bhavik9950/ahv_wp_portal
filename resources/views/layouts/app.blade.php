<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? '' }}{{ isset($title) ? ' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-base-200 text-base-content">
    <div class="drawer lg:drawer-open min-h-screen">
        <input id="app-drawer" type="checkbox" class="drawer-toggle" />

        <div class="drawer-content flex flex-col">
            {{-- Top bar --}}
            <header class="navbar bg-base-100 border-b border-base-300 sticky top-0 z-30 px-4">
                <div class="flex-none lg:hidden">
                    <label for="app-drawer" class="btn btn-square btn-ghost" aria-label="Open menu">
                        <i class="ti ti-menu-2 text-xl"></i>
                    </label>
                </div>
                <div class="flex-1">
                    <span class="text-lg font-semibold">{{ $header ?? ($title ?? 'Dashboard') }}</span>
                </div>
                <div class="flex-none">
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-ghost btn-sm gap-2">
                            <i class="ti ti-user-circle text-lg"></i>
                            <span class="hidden sm:inline">{{ auth()->user()?->name }}</span>
                        </div>
                        <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-40 w-52 p-2 shadow border border-base-300">
                            <li class="menu-title text-xs">{{ auth()->user()?->email }}</li>
                            <li><a href="{{ route('profile.edit') }}"><i class="ti ti-settings"></i> Profile</a></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left"><i class="ti ti-logout"></i> Log out</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <main class="p-4 sm:p-6 flex-1">
                @if (session('status'))
                    <div class="alert alert-success mb-4"><i class="ti ti-check"></i><span>{{ session('status') }}</span></div>
                @endif
                {{ $slot }}
            </main>
        </div>

        <div class="drawer-side z-40">
            <label for="app-drawer" aria-label="Close menu" class="drawer-overlay"></label>
            <aside class="bg-base-100 border-r border-base-300 w-72 min-h-full flex flex-col">
                <div class="px-4 py-4 border-b border-base-300">
                    <a href="{{ url('/dashboard') }}" class="flex items-center gap-2">
                        <i class="ti ti-brand-whatsapp text-2xl text-success"></i>
                        <span class="font-bold leading-tight">{{ config('app.name') }}</span>
                    </a>
                    @if ($org = app(\App\Support\CurrentOrganization::class)->resolve())
                        <p class="text-xs opacity-60 mt-1">{{ $org->name }}</p>
                    @endif
                </div>

                @include('partials.sidebar')
            </aside>
        </div>
    </div>

    <script type="application/json" id="flash-notify">@json(session('flash_notify'))</script>
</body>
</html>
