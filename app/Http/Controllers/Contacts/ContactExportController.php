<?php

declare(strict_types=1);

namespace App\Http\Controllers\Contacts;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactExportController extends Controller
{
    public function __invoke(Request $request, AuditLogger $audit): StreamedResponse
    {
        $this->authorize('export', Contact::class);

        $query = Contact::query()
            ->when($request->filled('opt_in'), fn ($q) => $q->where('opt_in_status', $request->string('opt_in')))
            ->when($request->filled('group'), fn ($q) => $q->whereHas('groups', fn ($g) => $g->whereKey($request->string('group'))))
            ->orderBy('created_at');

        $audit->log('contact.exported', null, ['filters' => $request->only(['opt_in', 'group'])]);

        $filename = 'contacts-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, ['name', 'phone_e164', 'country_code', 'email', 'opt_in_status', 'opted_in_at', 'created_at']);

            $query->chunk(1000, function ($contacts) use ($out): void {
                foreach ($contacts as $c) {
                    fputcsv($out, [
                        $c->name,
                        $c->phone_e164,
                        $c->country_code,
                        $c->email,
                        $c->opt_in_status->value,
                        optional($c->opted_in_at)->toIso8601String(),
                        $c->created_at?->toIso8601String(),
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
