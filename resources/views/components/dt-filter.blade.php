@props([
    'label',
    'target',       // CSS selector of the <table data-datatable>
    'col' => null,  // column index to filter
    'colName' => null,
    'match' => 'exact',
])

{{-- Labelled dropdown that filters a DataTable client-side (see resources/js/datatables.js). --}}
<label class="form-control w-full sm:w-auto">
    <span class="label-text text-xs font-medium opacity-70 mb-1">{{ $label }}</span>
    <select
        {{ $attributes->merge(['class' => 'select select-bordered select-sm sm:min-w-[10rem]']) }}
        data-dt-filter
        data-dt-target="{{ $target }}"
        @if (! is_null($col)) data-dt-col="{{ $col }}" @endif
        @if ($colName) data-dt-col-name="{{ $colName }}" @endif
        data-dt-match="{{ $match }}"
    >
        {{ $slot }}
    </select>
</label>
