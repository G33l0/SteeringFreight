@php
    $hours = chat_retention_hours();
    $documentAddress = setting('contact.operations_email') ?: setting('contact.email');
@endphp

<p {{ $attributes->merge(['class' => 'text-xs leading-relaxed text-ink-500']) }}>
    This conversation is cleared automatically {{ $hours }} hours after the last message, and
    files cannot be sent through it. Please keep documents and payment details out of the chat
    @if (filled($documentAddress))
        — send paperwork to <a href="mailto:{{ $documentAddress }}" class="underline underline-offset-2 hover:text-ink-800">{{ $documentAddress }}</a>
        quoting your tracking number.
    @else
        and send paperwork to the operations desk by email, quoting your tracking number.
    @endif
</p>
