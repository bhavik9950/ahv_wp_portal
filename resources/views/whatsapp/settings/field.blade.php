@php($ph = $ph ?? null)
@php($type = $type ?? 'text')
<div>
    <label class="label" for="{{ $name }}"><span class="label-text">{{ $label }}</span></label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
           value="{{ $value }}"
           @if ($ph) placeholder="{{ $ph }}" @endif
           autocomplete="{{ $type === 'password' ? 'new-password' : 'off' }}"
           class="input input-bordered w-full @error($name) input-error @enderror" />
    @error($name)<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
</div>
