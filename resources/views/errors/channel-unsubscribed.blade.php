<x-app-layout>
    <x-slot name="title">{{ ucfirst($channel) }} Service Not Subscribed</x-slot>
    <x-slot name="header">Module Access Restricted</x-slot>

    <div class="max-w-2xl mx-auto py-12 px-4">
        <div class="card bg-base-100 border border-base-300 shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-warning/20 via-base-200 to-primary/10 p-6 border-b border-base-300 flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-base-100 shadow flex items-center justify-center shrink-0 border border-base-300">
                    @if ($channel === 'sms')
                        <i class="ti ti-message-dots text-3xl text-warning"></i>
                    @elseif ($channel === 'voice')
                        <i class="ti ti-phone-outgoing text-3xl text-secondary"></i>
                    @else
                        <i class="ti ti-brand-whatsapp text-3xl text-success"></i>
                    @endif
                </div>
                <div>
                    <span class="badge badge-warning badge-sm gap-1 font-semibold uppercase tracking-wider">Plan Upgrade Required</span>
                    <h2 class="text-xl font-bold mt-1">{{ strtoupper($channel) }} Service Not Subscribed</h2>
                    <p class="text-xs opacity-70">Organization: <strong>{{ $organization->name }}</strong></p>
                </div>
            </div>

            <div class="card-body p-6 space-y-5">
                <p class="text-sm opacity-80 leading-relaxed">
                    Your organization is currently not entitled to access the 
                    <strong class="text-base-content">{{ ucfirst($channel) }} Portal</strong>.
                    To use high-throughput {{ strtoupper($channel) }} campaigns, templates, and analytics, you need to subscribe your organization to this service.
                </p>

                <div class="p-4 rounded-xl bg-base-200/60 border border-base-300 space-y-2">
                    <div class="text-xs font-bold uppercase tracking-wide opacity-70 flex items-center gap-1.5">
                        <i class="ti ti-sparkles text-warning"></i> What is included with the {{ strtoupper($channel) }} Module:
                    </div>
                    <ul class="text-xs space-y-1.5 opacity-80 pl-2">
                        @if ($channel === 'sms')
                            <li class="flex items-center gap-2"><i class="ti ti-check text-success"></i> Dedicated DLT Sender Header approval (e.g., {{ strtoupper(substr($organization->name, 0, 6)) }})</li>
                            <li class="flex items-center gap-2"><i class="ti ti-check text-success"></i> Ultra-fast Transactional OTPs & Service Alerts (< 2s delivery)</li>
                            <li class="flex items-center gap-2"><i class="ti ti-check text-success"></i> High-volume Bulk Promotional SMS Campaigns with DND auto-filtering</li>
                            <li class="flex items-center gap-2"><i class="ti ti-check text-success"></i> Real-time Delivery Reports (DLR) & Analytics</li>
                        @elseif ($channel === 'voice')
                            <li class="flex items-center gap-2"><i class="ti ti-check text-success"></i> Automated Outbound AI Telephony Calling Agent</li>
                            <li class="flex items-center gap-2"><i class="ti ti-check text-success"></i> Conversational Voice in English, Hindi & regional languages</li>
                            <li class="flex items-center gap-2"><i class="ti ti-check text-success"></i> Automated Call Transcriptions & Sentiment Analysis</li>
                        @else
                            <li class="flex items-center gap-2"><i class="ti ti-check text-success"></i> Official Meta WhatsApp Business API Cloud Access</li>
                            <li class="flex items-center gap-2"><i class="ti ti-check text-success"></i> Broadcast Campaigns to Thousands of Verified WhatsApp Contacts</li>
                            <li class="flex items-center gap-2"><i class="ti ti-check text-success"></i> Live 2-Way Multi-Agent Chat Inbox & AI Assistant</li>
                        @endif
                    </ul>
                </div>

                <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-base-300">
                    <a href="{{ url('/dashboard') }}" class="btn btn-outline btn-sm gap-2 w-full sm:w-auto">
                        <i class="ti ti-arrow-left"></i> Back to Main Dashboard
                    </a>

                    @if (\Illuminate\Support\Facades\Route::has('subscription.index'))
                        <a href="{{ route('subscription.index') }}" class="btn btn-primary btn-sm gap-2 w-full sm:w-auto">
                            <i class="ti ti-credit-card"></i> Upgrade Plan / View Subscriptions
                        </a>
                    @else
                        <a href="mailto:support@ahvsoftware.com?subject=Subscribe%20to%20{{ ucfirst($channel) }}%20Service%20-%20{{ urlencode($organization->name) }}" class="btn btn-primary btn-sm gap-2 w-full sm:w-auto">
                            <i class="ti ti-mail"></i> Contact AH&V Software
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
