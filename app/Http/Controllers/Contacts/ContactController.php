<?php

declare(strict_types=1);

namespace App\Http\Controllers\Contacts;

use App\Enums\OptInStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contacts\StoreContactRequest;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Services\Contacts\ContactService;
use App\Services\Contacts\DuplicateContactException;
use App\Services\Contacts\OptInService;
use Illuminate\Http\RedirectResponse;
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
}
