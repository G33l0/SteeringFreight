<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ company_name() }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
@if ($address = trim(collect([setting('contact.address_line_1'), setting('contact.city'), setting('contact.country')])->filter()->implode(', ')))
{{ $address }}<br>
@endif
@if ($phone = setting('contact.phone')){{ $phone }} &nbsp;·&nbsp; @endif
@if ($email = setting('contact.email'))<a href="mailto:{{ $email }}">{{ $email }}</a><br>@endif
© {{ date('Y') }} {{ setting('company.legal_name', company_name()) }}. {{ __('All rights reserved.') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
