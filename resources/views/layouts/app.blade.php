<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Turbo Drive: cross-fade between pages (Chrome View Transitions API). --}}
    <meta name="view-transition" content="same-origin">
    <title>{{ $title ?? '' }}{{ isset($title) ? ' · ' : '' }}{{ config('app.name') }}</title>
    {{ $head ?? '' }}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Bulletproof vertical alignment & text visibility for select dropdowns across the portal */
        select, .select {
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            vertical-align: middle !important;
        }
        .select-sm {
            height: 2.25rem !important;
            min-height: 2.25rem !important;
            line-height: 2.25rem !important;
            font-size: 0.8125rem !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            padding-left: 0.75rem !important;
            padding-right: 2rem !important;
        }
        .select:not(.select-sm):not(.select-lg):not(.select-xs) {
            height: 2.625rem !important;
            min-height: 2.625rem !important;
            line-height: 2.625rem !important;
            font-size: 0.875rem !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            padding-left: 0.875rem !important;
            padding-right: 2.5rem !important;
        }
        .dt-length select {
            height: 2.25rem !important;
            min-height: 2.25rem !important;
            line-height: 2.25rem !important;
            font-size: 0.8125rem !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            padding-left: 0.625rem !important;
            padding-right: 1.75rem !important;
        }
    </style>
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
                <div class="flex-1 flex items-center gap-3">
                    <span class="text-lg font-semibold">{{ $header ?? ($title ?? 'Dashboard') }}</span>
                    @php($activeOrg = app(\App\Support\CurrentOrganization::class)->resolve())
                    @if ($activeOrg)
                        <span class="badge badge-sm badge-outline gap-1 hidden sm:inline-flex text-xs opacity-80" title="Active Organization">
                            <i class="ti ti-building text-xs"></i> {{ $activeOrg->name }}
                        </span>
                    @endif
                </div>
                <div class="flex-none flex items-center gap-2">
                    @php($currentUser = auth()->user())
                    @if ($currentUser)
                        @php($availableOrgs = $currentUser->isSuperAdmin() ? \App\Models\Organization::withoutGlobalScopes()->orderBy('name')->get() : $currentUser->organizations)
                        @if ($availableOrgs->count() > 1)
                            <div class="dropdown dropdown-end">
                                <div tabindex="0" role="button" class="btn btn-ghost btn-xs gap-1 border border-base-300">
                                    <i class="ti ti-switch-horizontal text-xs"></i>
                                    <span class="hidden md:inline text-xs">Switch Org</span>
                                </div>
                                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-40 w-56 p-2 shadow border border-base-300">
                                    <li class="menu-title text-xs">Organizations</li>
                                    @foreach ($availableOrgs as $orgItem)
                                        <li>
                                            <form method="POST" action="{{ route('organization.switch') }}" class="p-0">
                                                @csrf
                                                <input type="hidden" name="organization_id" value="{{ $orgItem->getKey() }}">
                                                <button type="submit" class="w-full text-left flex items-center justify-between py-1.5 px-3 {{ $activeOrg && $activeOrg->getKey() === $orgItem->getKey() ? 'active font-medium' : '' }}">
                                                    <span class="truncate">{{ $orgItem->name }}</span>
                                                    @if ($activeOrg && $activeOrg->getKey() === $orgItem->getKey())
                                                        <i class="ti ti-check text-xs"></i>
                                                    @endif
                                                </button>
                                            </form>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endif

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
                @php($currentOrg = app(\App\Support\CurrentOrganization::class)->resolve())
                @if ($currentOrg && $currentOrg->isSubscriptionExpired() && ! auth()->user()?->isSuperAdmin())
                    <div class="alert alert-error mb-4 shadow-sm border border-error/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <i class="ti ti-alert-triangle text-2xl shrink-0"></i>
                            <div>
                                <div class="font-bold text-sm">Subscription Has Ended</div>
                                <div class="text-xs opacity-90">Your subscription expired on {{ $currentOrg->subscription_expires_at?->format('d M Y') ?? 'recently' }}. Your portal is currently in read-only mode. Kindly renew to send messages, create templates, or launch campaigns.</div>
                            </div>
                        </div>
                        @if (\Illuminate\Support\Facades\Route::has('subscription.index'))
                            <a href="{{ route('subscription.index') }}" class="btn btn-sm btn-neutral shrink-0">
                                <i class="ti ti-credit-card"></i> Renew Now
                            </a>
                        @endif
                    </div>
                @endif

                @if (session('status'))
                    <div class="alert alert-success mb-4"><i class="ti ti-check"></i><span>{{ session('status') }}</span></div>
                @endif
                {{ $slot }}
            </main>
        </div>

        <div class="drawer-side z-40">
            <label for="app-drawer" aria-label="Close menu" class="drawer-overlay"></label>
            <aside id="app-sidebar" data-turbo-permanent
                   class="bg-base-100 border-r border-base-300 w-72 min-h-full flex flex-col">
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
