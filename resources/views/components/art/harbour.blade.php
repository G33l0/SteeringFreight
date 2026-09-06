@props(['class' => ''])

{{-- Container terminal drawn in line and block form, used behind the hero when
     no photograph has been uploaded in the site settings. --}}
<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 1800 900" preserveAspectRatio="xMidYMax slice"
     fill="none" role="img" aria-label="Illustration of a container terminal with gantry cranes and a moored container vessel">
    <defs>
        <linearGradient id="harbour-sky" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#07131e"/>
            <stop offset="65%" stop-color="#102639"/>
            <stop offset="100%" stop-color="#173450"/>
        </linearGradient>
    </defs>

    <rect width="1800" height="900" fill="url(#harbour-sky)"/>

    <g transform="translate(430,0)">
    {{-- Skyline --}}
    <g fill="#24506f" opacity="0.6">
        <rect x="150" y="520" width="34" height="98"/>
        <rect x="192" y="548" width="22" height="70"/>
        <rect x="222" y="530" width="40" height="88"/>
        <rect x="1470" y="540" width="30" height="78"/>
        <rect x="1508" y="516" width="44" height="102"/>
        <rect x="1560" y="550" width="26" height="68"/>
    </g>

    {{-- Gantry cranes --}}
    <g stroke="#4f7a9c" stroke-width="3" opacity="0.9">
        <path d="M420 618V470h110v148M420 470h-30l30-34h110l30 34h-30M474 470v72"/>
        <path d="M620 618V482h104v136M620 482h-27l27-32h104l27 32h-27M672 482v66"/>
        <path d="M820 618V472h110v146M820 472h-30l30-34h110l30 34h-30M874 472v70"/>
        <path d="M1020 618V484h104v134M1020 484h-27l27-32h104l27 32h-27M1072 484v64"/>
    </g>

    {{-- Quay --}}
    <rect y="618" width="1800" height="8" fill="#2c5878" opacity="0.85"/>

    {{-- Stacked containers on the quay --}}
    <g opacity="0.85">
        <rect x="230" y="582" width="64" height="18" fill="#36678a"/>
        <rect x="230" y="600" width="64" height="18" fill="#2a5474"/>
        <rect x="300" y="600" width="64" height="18" fill="#36678a"/>
        <rect x="1330" y="582" width="64" height="18" fill="#2a5474"/>
        <rect x="1330" y="600" width="64" height="18" fill="#36678a"/>
        <rect x="1400" y="600" width="64" height="18" fill="#2a5474"/>
    </g>

    {{-- Vessel --}}
    <g>
        <path d="M420 712h700l-46 58H466z" fill="#0d2131" stroke="#4f7a9c" stroke-width="3"/>
        <rect x="1010" y="644" width="86" height="68" fill="#1b3d59" stroke="#4f7a9c" stroke-width="3"/>
        <g fill="#a2bacb" opacity="0.6">
            <rect x="1024" y="660" width="16" height="12"/>
            <rect x="1052" y="660" width="16" height="12"/>
            <rect x="1024" y="682" width="16" height="12"/>
            <rect x="1052" y="682" width="16" height="12"/>
        </g>

        {{-- Deck cargo --}}
        <g opacity="0.95">
            <rect x="450" y="682" width="70" height="30" fill="#a8501d"/>
            <rect x="526" y="682" width="70" height="30" fill="#36678a"/>
            <rect x="602" y="682" width="70" height="30" fill="#4a7699"/>
            <rect x="678" y="682" width="70" height="30" fill="#a8501d"/>
            <rect x="754" y="682" width="70" height="30" fill="#36678a"/>
            <rect x="830" y="682" width="70" height="30" fill="#4a7699"/>
            <rect x="906" y="682" width="70" height="30" fill="#2a5474"/>

            <rect x="450" y="650" width="70" height="28" fill="#2a5474"/>
            <rect x="526" y="650" width="70" height="28" fill="#9c4715"/>
            <rect x="602" y="650" width="70" height="28" fill="#36678a"/>
            <rect x="678" y="650" width="70" height="28" fill="#2a5474"/>
            <rect x="754" y="650" width="70" height="28" fill="#4a7699"/>
            <rect x="830" y="650" width="70" height="28" fill="#36678a"/>

            <rect x="526" y="620" width="70" height="26" fill="#36678a"/>
            <rect x="602" y="620" width="70" height="26" fill="#2a5474"/>
            <rect x="678" y="620" width="70" height="26" fill="#9c4715"/>
            <rect x="754" y="620" width="70" height="26" fill="#2a5474"/>
        </g>
    </g>

    </g>

    {{-- Water --}}
    <rect y="770" width="1800" height="130" fill="#050f18"/>
    <g stroke="#3d6382" stroke-width="2" opacity="0.5">
        <path d="M120 792h240M420 812h300M780 792h220M1060 814h340M300 846h420M900 850h380"/>
    </g>
</svg>
