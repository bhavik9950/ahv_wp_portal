<x-app-layout>
    <x-slot name="title">Import Contacts</x-slot>

    <div class="max-w-lg space-y-4">
        <div class="alert alert-info text-sm">
            <i class="ti ti-info-circle"></i>
            <span>Upload a CSV with a header row. You'll map columns and preview the results before anything is imported.</span>
        </div>

        <form method="POST" action="{{ route('whatsapp.contacts.import.store') }}" enctype="multipart/form-data"
              class="card bg-base-100 border border-base-300">
            @csrf
            <div class="card-body space-y-4">
                <div>
                    <label class="label"><span class="label-text">CSV file (max 10 MB)</span></label>
                    <input type="file" name="file" accept=".csv,text/csv" class="file-input file-input-bordered w-full" required>
                    @error('file')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <button class="btn btn-primary"><i class="ti ti-upload"></i> Upload &amp; continue</button>
            </div>
        </form>
    </div>
</x-app-layout>
