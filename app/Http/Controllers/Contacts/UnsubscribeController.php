<?php

declare(strict_types=1);

namespace App\Http\Controllers\Contacts;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\Contacts\OptInService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Public, signature-verified unsubscribe page. Link built with
 * URL::signedRoute('unsubscribe', ['publicContact' => $contact->getKey()]) and
 * embedded in message footers where appropriate.
 */
class UnsubscribeController extends Controller
{
    public function show(Contact $publicContact): View
    {
        return view('public.unsubscribe', [
            'contact' => $publicContact,
            'done' => $publicContact->isOptedOut(),
        ]);
    }

    public function update(Contact $publicContact, OptInService $optIn, TenantContext $tenant): RedirectResponse
    {
        $tenant->set($publicContact->organization()->firstOrFail());

        if (! $publicContact->isOptedOut()) {
            $optIn->optOut($publicContact, ['source' => 'unsubscribe_link']);
        }

        return back()->with('done', true);
    }
}
