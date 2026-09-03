<?php

declare(strict_types=1);

namespace App\Http\Controllers\Contacts;

use App\Enums\OptInStatus;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contacts\StoreContactRequest;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Services\Contacts\ContactService;
use App\Services\Contacts\DuplicateContactException;
use App\Services\Contacts\OptInService;
use App\Support\Scoped;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function __construct(
        private readonly ContactService $contacts,
        private readonly OptInService $optIn,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Contact::class);

        $limit = 2000;

        $contacts = Contact::query()
            ->with('groups')
            ->latest()
            ->limit($limit + 1)
            ->get();

        // Search / filter / sort are handled client-side by DataTables.
        return view('contacts.index', [
            'contacts' => $contacts->take($limit),
            'capped' => $contacts->count() > $limit,
            'limit' => $limit,
            'groups' => ContactGroup::query()->orderBy('name')->get(),
            'optInStatuses' => OptInStatus::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Contact::class);

        return view('contacts.create', ['groups' => ContactGroup::query()->orderBy('name')->get()]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $this->authorize('create', Contact::class);

        try {
            $contact = $this->contacts->create($request->validated());
        } catch (DuplicateContactException $e) {
            return back()->withInput()->withErrors(['phone' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['phone' => $e->getMessage()]);
        }

        if ($request->filled('groups')) {
            $contact->groups()->sync($request->validated('groups'));
        }

        return redirect()
            ->route('whatsapp.contacts.show', $contact)
            ->with('flash_notify', ['type' => 'success', 'message' => 'Contact added.']);
    }

    public function show(Contact $contact): View
    {
        $this->authorize('view', $contact);

        $contact->load(['groups', 'optInRecords.campaign' => fn ($q) => $q]);

        return view('contacts.show', ['contact' => $contact]);
    }

    public function update(StoreContactRequest $request, Contact $contact): RedirectResponse
    {
        $this->authorize('update', $contact);

        try {
            $this->contacts->update($contact, $request->validated());
        } catch (DuplicateContactException $e) {
            return back()->withInput()->withErrors(['phone' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['phone' => $e->getMessage()]);
        }

        $contact->groups()->sync($request->validated('groups', []));

        return back()->with('flash_notify', ['type' => 'success', 'message' => 'Contact updated.']);
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $this->authorize('delete', $contact);

        $this->contacts->delete($contact);

        return redirect()
            ->route('whatsapp.contacts.index')
            ->with('flash_notify', ['type' => 'success', 'message' => 'Contact deleted.']);
    }

    public function optIn(Contact $contact): RedirectResponse
    {
        $this->authorize('update', $contact);
        $this->optIn->optIn($contact, ['source' => 'portal']);

        return back()->with('flash_notify', ['type' => 'success', 'message' => 'Contact marked as opted in.']);
    }

    public function optOut(Contact $contact): RedirectResponse
    {
        $this->authorize('update', $contact);
        $this->optIn->optOut($contact, ['source' => 'portal']);

        return back()->with('flash_notify', ['type' => 'warning', 'message' => 'Contact marked as opted out.']);
    }

    /**
     * Record an opt-in / opt-out decision for many contacts at once. This is a
     * consent *record* — only mark opted-in when you actually hold consent
     * (WhatsApp requires it for MARKETING sends).
     */
    public function bulkOptIn(Request $request): RedirectResponse
    {
        abort_unless(
            (bool) $request->user()?->can(Permission::ContactManage->value),
            403,
        );

        $data = $request->validate([
            'contact_ids' => ['required', 'array', 'min:1', 'max:5000'],
            'contact_ids.*' => ['string', Scoped::exists('contacts')],
            'action' => ['required', 'in:opt_in,opt_out'],
            'source' => ['nullable', 'string', 'max:80'],
        ]);

        $contacts = Contact::query()->whereKey($data['contact_ids'])->get();
        $context = ['source' => $data['source'] ?? 'bulk_portal'];

        foreach ($contacts as $contact) {
            $data['action'] === 'opt_in'
                ? $this->optIn->optIn($contact, $context)
                : $this->optIn->optOut($contact, $context);
        }

        return back()->with('flash_notify', [
            'type' => $data['action'] === 'opt_in' ? 'success' : 'warning',
            'message' => $contacts->count().' contact(s) marked as '.($data['action'] === 'opt_in' ? 'opted in' : 'opted out').'.',
        ]);
    }
}
