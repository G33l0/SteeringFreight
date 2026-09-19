@props(['name', 'class' => 'h-5 w-5'])

@php
    $paths = [
        'train' => '<rect x="5" y="4" width="14" height="12" rx="2"/><path d="M5 10h14"/><path d="M8 20l-2 2M16 20l2 2"/><circle cx="9" cy="13.5" r="1"/><circle cx="15" cy="13.5" r="1"/><path d="M7 16h10"/>',
        'home' => '<path d="M4 11 12 4l8 7v9H4z"/><path d="M10 20v-5h4v5"/>',
        'flag' => '<path d="M5 21V4"/><path d="M5 5h11l-2 3.5L16 12H5z"/>',
        'container' => '<path d="M3 7h18v11H3z"/><path d="M7 7v11M11 7v11M15 7v11M19 7v11"/><path d="M2 18h20"/>',
        'vessel' => '<path d="M3 17l1.6-5.2a1 1 0 0 1 .96-.8H18.4a1 1 0 0 1 .96.8L21 17"/><path d="M7 11V7h8v4"/><path d="M11 7V4h2v3"/><path d="M2 20c1.6 0 1.6-1.2 3.2-1.2S6.8 20 8.4 20s1.6-1.2 3.2-1.2S13.2 20 14.8 20s1.6-1.2 3.2-1.2S19.6 20 21.2 20"/>',
        'aircraft' => '<path d="M10.5 20.5 12 15l7.5-2.2a2 2 0 0 0-.6-3.9L14 9.5 9.5 3.5h-2l2.2 6.6-4 1.1-2-2.2H2l1.6 3.8L2 16.6h1.7l2-2.2 4 1.1L7.5 22h2z"/>',
        'customs' => '<path d="M12 3l8 3v5c0 4.6-3.2 8.4-8 10-4.8-1.6-8-5.4-8-10V6z"/><path d="m9 12 2 2 4-4"/>',
        'warehouse' => '<path d="M3 21V9l9-5 9 5v12"/><path d="M7 21v-7h10v7"/><path d="M7 17h10"/>',
        'truck' => '<path d="M3 16V6h11v10"/><path d="M14 9h4l3 3.5V16h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="17.5" cy="18" r="2"/>',
        'consolidation' => '<path d="M4 8h7v7H4z"/><path d="M13 12h7v7h-7z"/><path d="M8 15v4h5"/>',
        'documents' => '<path d="M8 3h7l4 4v14H8z"/><path d="M15 3v4h4"/><path d="M11 12h5M11 16h5"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'phone' => '<path d="M5 3h3l2 5-2.2 1.4a12 12 0 0 0 5.8 5.8L15 13l5 2v3a2 2 0 0 1-2.2 2A16.8 16.8 0 0 1 3 5.2 2 2 0 0 1 5 3z"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="1.5"/><path d="m3.5 6.5 8.5 6 8.5-6"/>',
        'pin' => '<path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'check' => '<path d="m4 12.5 5 5L20 6.5"/>',
        'alert' => '<path d="M12 4 2.5 20h19z"/><path d="M12 10v4M12 17h.01"/>',
        'arrow-right' => '<path d="M4 12h15"/><path d="m13 6 6 6-6 6"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close' => '<path d="m6 6 12 12M18 6 6 18"/>',
        'download' => '<path d="M12 4v11"/><path d="m7.5 11.5 4.5 4 4.5-4"/><path d="M4 20h16"/>',
        'chat' => '<path d="M4 5h16v11H9l-5 4z"/>',
        'user' => '<circle cx="12" cy="8" r="3.5"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0"/>',
        'dashboard' => '<path d="M4 4h7v7H4zM13 4h7v4h-7zM13 10h7v10h-7zM4 13h7v7H4z"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1.2l2-1.5-2-3.4-2.3 1a7 7 0 0 0-2-1.2L14.2 3H9.8l-.4 2.7a7 7 0 0 0-2 1.2l-2.3-1-2 3.4 2 1.5a7.3 7.3 0 0 0 0 2.4l-2 1.5 2 3.4 2.3-1a7 7 0 0 0 2 1.2l.4 2.7h4.4l.4-2.7a7 7 0 0 0 2-1.2l2.3 1 2-3.4-2-1.5c.06-.4.1-.8.1-1.2z"/>',
        'list' => '<path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01"/>',
        'star' => '<path d="m12 4 2.4 5 5.6.8-4 3.9 1 5.5-5-2.6-5 2.6 1-5.5-4-3.9 5.6-.8z"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'quote' => '<path d="M7 7h10v10H7z"/><path d="M10 11h4M10 14h4"/>',
        'log' => '<path d="M5 4h14v16H5z"/><path d="M8 9h8M8 13h8M8 17h5"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => $class, 'fill' => 'none', 'stroke-width' => '1.6']) }}
     viewBox="0 0 24 24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    {!! $paths[$name] ?? $paths['container'] !!}
</svg>
