@props(['message' => 'Nothing to show yet.'])

<p {{ $attributes->merge(['class' => 'px-4 py-8 text-center text-sm text-ink-500']) }}>{{ $message }}</p>
