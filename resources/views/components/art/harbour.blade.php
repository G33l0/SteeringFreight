@props(['class' => ''])

{{-- Harbour scene used behind the hero when no photograph has been uploaded. --}}
<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 1200 520" preserveAspectRatio="xMidYMax slice"
     fill="none" role="img" aria-label="Illustration of a container terminal with gantry cranes and a moored vessel">
    <defs>
        <linearGradient id="sky" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#0c1f2e"/>
            <stop offset="100%" stop-color="#14304a"/>
        </linearGradient>
    </defs>

    <rect width="1200" height="520" fill="url(#sky)"/>

    {{-- Distant skyline --}}
    <g fill="#1d4160" opacity="0.55">
        <rect x="40" y="250" width="46" height="120"/>
        <rect x="96" y="286" width="30" height="84"/>
        <rect x="136" y="264" width="54" height="106"/>
        <rect x="1010" y="272" width="40" height="98"/>
        <rect x="1060" y="248" width="58" height="122"/>
        <rect x="1128" y="288" width="34" height="82"/>
    </g>

    {{-- Gantry cranes --}}
    <g stroke="#446e8c" stroke-width="4" opacity="0.85">
        <path d="M250 370V190h150v180M250 190h-40l40-46h150l40 46h-40M325 190v100"/>
        <path d="M520 370V210h140v160M520 210h-36l36-42h140l36 42h-36M590 210v92"/>
        <path d="M790 370V196h150v174M790 196h-40l40-44h150l40 44h-40M865 196v96"/>
    </g>

    {{-- Vessel --}}
    <g>
        <path d="M300 424h620l-42 52H342z" fill="#0c1f2e" stroke="#446e8c" stroke-width="3"/>
        <rect x="808" y="360" width="86" height="64" fill="#14304a" stroke="#446e8c" stroke-width="3"/>
        <rect x="822" y="374" width="16" height="14" fill="#a2bacb" opacity="0.75"/>
        <rect x="848" y="374" width="16" height="14" fill="#a2bacb" opacity="0.75"/>
        <g fill="#c2631f">
            <rect x="330" y="392" width="70" height="30"/>
            <rect x="486" y="392" width="70" height="30"/>
            <rect x="642" y="392" width="70" height="30"/>
        </g>
        <g fill="#2c5878">
            <rect x="408" y="392" width="70" height="30"/>
            <rect x="564" y="392" width="70" height="30"/>
            <rect x="720" y="392" width="70" height="30"/>
            <rect x="330" y="360" width="70" height="28"/>
            <rect x="486" y="360" width="70" height="28"/>
            <rect x="642" y="360" width="70" height="28"/>
        </g>
        <g fill="#446e8c" opacity="0.7">
            <rect x="408" y="360" width="70" height="28"/>
            <rect x="564" y="360" width="70" height="28"/>
        </g>
    </g>

    {{-- Quay and water --}}
    <rect y="476" width="1200" height="44" fill="#07131e"/>
    <g stroke="#2c5878" stroke-width="2" opacity="0.5">
        <path d="M60 492h180M280 502h240M600 492h190M840 504h260"/>
    </g>
</svg>
