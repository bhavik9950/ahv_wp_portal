<?php

declare(strict_types=1);

namespace App\Http\Controllers\Contacts;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeContactImportJob;
use App\Jobs\CommitContactImportJob;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\ContactImport;
use App\Support\Scoped;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use League\Csv\Reader;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactImportController extends Controller
{
    public function create(): View
    {
        $this->authorize('import', Contact::class);

        return view('contacts.import.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('import', Contact::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'], // 10 MB
        ]);

        $file = $request->file('file');
        $path = 'imports/'.Str::ulid().'.csv';
        Storage::disk('local')->put($path, $file->getContent());

        // Read the header row for the column-mapping step.
        $reader = Reader::createFromString(Storage::disk('local')->get($path) ?? '');
        $reader->setHeaderOffset(0);
        $headers = $reader->getHeader();

        $import = ContactImport::query()->create([
            'original_filename' => $file->getClientOriginalName(),
            'disk' => 'local',
            'path' => $path,
        ]);

        return redirect()->route('whatsapp.contacts.import.map', [$import, 'headers' => implode(',', $headers)]);
    }

    public function map(ContactImport $import): View
    {
        $this->authorize('import', Contact::class);

        $reader = Reader::createFromString(Storage::disk($import->disk)->get($import->path) ?? '');
        $reader->setHeaderOffset(0);

        return view('contacts.import.map', [
            'import' => $import,
            'headers' => $reader->getHeader(),
            'sample' => collect($reader->getRecords())->take(3)->values()->all(),
            'groups' => ContactGroup::query()->orderBy('name')->get(),
            'fields' => ['' => '— ignore —', 'name' => 'Name', 'phone' => 'Phone', 'country_code' => 'Country code', 'email' => 'Email'],
        ]);
    }

    public function analyze(Request $request, ContactImport $import): RedirectResponse
    {
        $this->authorize('import', Contact::class);

        $data = $request->validate([
            'column_map' => ['required', 'array'],
            'group_id' => ['nullable', 'string', Scoped::exists('contact_groups')],
            'opt_in_source' => ['nullable', 'string', 'max:80'],
            'mark_opted_in' => ['nullable', 'boolean'],
        ]);

        if (! in_array('phone', $data['column_map'], true)) {
            return back()->withErrors(['column_map' => 'One column must be mapped to “Phone”.']);
        }

        $import->update([
            'status' => 'queued',
            'column_map' => array_filter($data['column_map']),
            'options' => [
                'group_id' => $data['group_id'] ?? null,
                'opt_in_source' => $data['opt_in_source'] ?? 'csv_import',
                'mark_opted_in' => (bool) ($data['mark_opted_in'] ?? false),
            ],
        ]);

        AnalyzeContactImportJob::dispatch($import->getKey())->onQueue('whatsapp-media');

        return redirect()->route('whatsapp.contacts.import.show', $import);
    }

    public function show(ContactImport $import): View
    {
        $this->authorize('import', Contact::class);

        return view('contacts.import.show', ['import' => $import]);
    }

    public function commit(ContactImport $import): RedirectResponse
    {
        $this->authorize('import', Contact::class);

        abort_unless($import->isAnalyzed(), 409, 'Import is not ready to commit.');

        $import->update(['status' => 'importing', 'imported_rows' => 0]);
        CommitContactImportJob::dispatch($import->getKey())->onQueue('whatsapp-media');

        return redirect()->route('whatsapp.contacts.import.show', $import)
            ->with('flash_notify', ['type' => 'info', 'message' => 'Import started — valid rows are being added.']);
    }

    public function errors(ContactImport $import): StreamedResponse
    {
        $this->authorize('import', Contact::class);

        abort_if($import->error_report_path === null, 404);

        return Storage::disk($import->disk)->download(
            $import->error_report_path,
            Str::slug(pathinfo($import->original_filename, PATHINFO_FILENAME)).'-invalid-rows.csv',
        );
    }
}
