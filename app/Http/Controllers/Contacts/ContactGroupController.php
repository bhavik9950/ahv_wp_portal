<?php

declare(strict_types=1);

namespace App\Http\Controllers\Contacts;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactGroupController extends Controller
{
    public function index(): View
    {
        $this->authorizePermission(Permission::ContactView);

        return view('contacts.groups.index', [
            'groups' => ContactGroup::query()->withCount('contacts')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission(Permission::ContactManage);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        ContactGroup::query()->firstOrCreate(['name' => $data['name']], ['description' => $data['description'] ?? null]);

        return back()->with('flash_notify', ['type' => 'success', 'message' => 'Group created.']);
    }

    public function update(Request $request, ContactGroup $group): RedirectResponse
    {
        $this->authorizePermission(Permission::ContactManage);

        $group->update($request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
        ]));

        return back()->with('flash_notify', ['type' => 'success', 'message' => 'Group updated.']);
    }

    public function destroy(ContactGroup $group): RedirectResponse
    {
        $this->authorizePermission(Permission::ContactManage);

        $group->contacts()->detach();
        $group->delete();

        return back()->with('flash_notify', ['type' => 'success', 'message' => 'Group deleted.']);
    }

    /**
     * Bulk assign / remove contacts to a group from the contacts list.
     */
    public function assign(Request $request): RedirectResponse
    {
        $this->authorizePermission(Permission::ContactManage);

        $data = $request->validate([
            'group_id' => ['required', 'string', 'exists:contact_groups,id'],
            'contact_ids' => ['required', 'array', 'min:1'],
            'contact_ids.*' => ['string', 'exists:contacts,id'],
            'action' => ['required', 'in:add,remove'],
        ]);

        /** @var ContactGroup $group */
        $group = ContactGroup::query()->findOrFail($data['group_id']);
        $ids = Contact::query()->whereKey($data['contact_ids'])->pluck('id')->all();

        $data['action'] === 'add'
            ? $group->contacts()->syncWithoutDetaching($ids)
            : $group->contacts()->detach($ids);

        return back()->with('flash_notify', [
            'type' => 'success',
            'message' => count($ids).' contact(s) '.($data['action'] === 'add' ? 'added to' : 'removed from').' “'.$group->name.'”.',
        ]);
    }

    private function authorizePermission(Permission $permission): void
    {
        abort_unless((bool) request()->user()?->can($permission->value), 403);
    }
}
