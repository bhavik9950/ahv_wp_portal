@php
    $isSmsSuite = request()->is('sms*');

    /**
     * Nav item helper: only renders a link when the route exists and the user
     * passes the (optional) ability check. Falls back to a disabled item so the
     * IA from plan §41 stays visible while modules are still being built.
     */
    $nav = function (string $label, string $icon, ?string $routeName = null, ?string $ability = null, ?string $badge = null) {
        $url = $routeName && \Illuminate\Support\Facades\Route::has($routeName) ? route($routeName) : null;
        $allowed = ! $ability || auth()->user()?->can($ability);
        $active = $url && (request()->url() === $url || request()->routeIs($routeName));

        return view('partials.sidebar-item', compact('label', 'icon', 'url', 'allowed', 'active', 'badge'));
    };
@endphp

<ul class="menu px-2 py-3 gap-0.5 flex-1 overflow-y-auto text-sm">
    @if ($isSmsSuite)
        {{-- SMS SUITE SIDEBAR --}}
        <div class="px-2 pb-2 mb-2 border-b border-base-300">
            <a href="{{ route('dashboard') }}" class="btn btn-xs btn-outline btn-success gap-1.5 w-full justify-start text-[0.7rem]">
                <i class="ti ti-brand-whatsapp"></i> Switch to WhatsApp
            </a>
        </div>

        {!! $nav('SMS Dashboard', 'ti-layout-dashboard', 'sms.dashboard') !!}

        <li class="menu-title mt-3 text-warning font-bold">SMS Gateway</li>
        {!! $nav('DLT Sender IDs', 'ti-certificate', 'sms.headers.index') !!}
        {!! $nav('DLT Templates', 'ti-template', 'sms.templates.index') !!}
        {!! $nav('Quick SMS', 'ti-send', 'sms.quick.create') !!}
        {!! $nav('SMS Campaigns', 'ti-rocket', 'sms.campaigns.index') !!}
        {!! $nav('SMS Reports &amp; DLR', 'ti-chart-bar', 'sms.reports.index') !!}

        <li class="menu-title mt-3">Audience &amp; Contacts</li>
        {!! $nav('Contacts', 'ti-address-book', 'whatsapp.contacts.index') !!}
        {!! $nav('Groups', 'ti-users-group', 'whatsapp.groups.index') !!}
        {!! $nav('Import Contacts', 'ti-file-import', 'whatsapp.contacts.import.create') !!}

        <li class="menu-title mt-3">Account</li>
        {!! $nav('SMS Wallet &amp; Billing', 'ti-wallet', 'subscription.index') !!}
    @else
        {{-- WHATSAPP SUITE SIDEBAR --}}
        <div class="px-2 pb-2 mb-2 border-b border-base-300">
            <a href="{{ route('sms.dashboard') }}" class="btn btn-xs btn-outline btn-warning gap-1.5 w-full justify-start text-[0.7rem]">
                <i class="ti ti-message-dots"></i> Switch to SMS Portal
            </a>
        </div>

        {!! $nav('Dashboard', 'ti-layout-dashboard', 'dashboard') !!}

        <li class="menu-title mt-3 text-success font-bold">WhatsApp</li>
        {!! $nav('Overview', 'ti-brand-whatsapp', 'whatsapp.overview') !!}
        {!! $nav('Phone Numbers', 'ti-phone', 'whatsapp.phone-numbers.index', \App\Enums\Permission::WabaView->value) !!}
        {!! $nav('Templates', 'ti-template', 'whatsapp.templates.index', \App\Enums\Permission::TemplateView->value) !!}
        {!! $nav('Contacts', 'ti-address-book', 'whatsapp.contacts.index', \App\Enums\Permission::ContactView->value) !!}
        {!! $nav('Groups', 'ti-users-group', 'whatsapp.groups.index', \App\Enums\Permission::ContactView->value) !!}
        {!! $nav('Import Contacts', 'ti-file-import', 'whatsapp.contacts.import.create', \App\Enums\Permission::ContactImport->value) !!}
        {!! $nav('Test Send', 'ti-send-2', 'whatsapp.test-send.create', \App\Enums\Permission::MessageSend->value) !!}
        {!! $nav('Campaigns', 'ti-rocket', 'whatsapp.campaigns.index', \App\Enums\Permission::CampaignView->value) !!}
        {!! $nav('Voice Campaigns', 'ti-phone-outgoing', 'whatsapp.voice-campaigns.index', \App\Enums\Permission::CampaignView->value, 'soon') !!}
        {!! $nav('Messages', 'ti-messages', 'whatsapp.messages.index', \App\Enums\Permission::MessageView->value) !!}
        {!! $nav('Chats', 'ti-message-circle-2', 'whatsapp.conversations.index', \App\Enums\Permission::MessageView->value) !!}
        {!! $nav('Assistant', 'ti-robot', 'whatsapp.assistant.conversations', \App\Enums\Permission::MessageView->value, 'soon') !!}
        {!! $nav('Assistant Settings', 'ti-adjustments-alt', 'whatsapp.assistant.settings', \App\Enums\Permission::WabaManage->value, 'soon') !!}
        {!! $nav('Media', 'ti-photo', 'whatsapp.media.index', \App\Enums\Permission::CampaignView->value) !!}
        {!! $nav('Reports', 'ti-chart-bar', 'whatsapp.reports.index', \App\Enums\Permission::ReportView->value) !!}
        {!! $nav('Webhooks', 'ti-webhook', 'whatsapp.webhooks.index', \App\Enums\Permission::WabaView->value) !!}
        {!! $nav('Settings', 'ti-settings-2', 'whatsapp.settings.edit', \App\Enums\Permission::WabaView->value) !!}
        {!! $nav('Subscription &amp; Billing', 'ti-credit-card', 'subscription.index') !!}
    @endif

    @php
        $user = auth()->user();
        $isSuper = $user?->isSuperAdmin() ?? false;
    @endphp

    {{-- Admin section is strictly for Platform Super Admins only --}}
    @if ($isSuper)
        <li class="menu-title mt-3">Platform Admin</li>
        {!! $nav('Lead Inquiries', 'ti-mail-fast', 'admin.inquiries.index') !!}
        {!! $nav('Subscriptions &amp; UTRs', 'ti-receipt-2', 'admin.subscriptions.index') !!}
        {!! $nav('Client Organizations', 'ti-building', 'admin.organizations.index') !!}
        {!! $nav('System Health', 'ti-heartbeat', 'admin.health') !!}
        {!! $nav('Emergency Controls', 'ti-alert-triangle', 'admin.controls') !!}
    @endif
</ul>
