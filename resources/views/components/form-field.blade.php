@props(['name', 'label', 'type' => 'text', 'required' => false])

<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="mb-2 block text-sm font-semibold">
        {{ $label }} @if ($required)<span class="text-accent">*</span>@endif
    </label>
    @if ($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" rows="6" @required($required) {{ $attributes->except('class')->merge(['class' => 'form-input']) }}>{{ old($name) }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name) }}" @required($required) {{ $attributes->except('class')->merge(['class' => 'form-input']) }}>
    @endif
    @error($name)
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
