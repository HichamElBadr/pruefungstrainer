@php
    $links = [
        [
            'label' => 'SQL-Aufgaben',
            'description' => 'Datenbanken abfragen',
            'href' => route('sql-uebung'),
            'active' => request()->routeIs('sql-uebung*') || request()->is('it/sql-uebung*'),
        ],
        [
            'label' => 'UML-Aufgaben',
            'description' => 'Klassendiagramme üben',
            'href' => route('uml.form'),
            'active' => request()->routeIs('uml.*') || request()->is('it/uml*'),
        ],
        [
            'label' => 'Rechenaufgaben',
            'description' => 'IT-Rechnen trainieren',
            'href' => route('calculation-exercises.index'),
            'active' => request()->routeIs('calculation-exercises.*') || request()->is('it/calculation-exercises*'),
        ],
    ];

    $historyRoute = null;

    foreach (['exercise-history.index', 'exercises.history', 'aufgabenverlauf', 'aufgabenverlauf.index'] as $candidate) {
        if (\Illuminate\Support\Facades\Route::has($candidate)) {
            $historyRoute = $candidate;
            break;
        }
    }

    if ($historyRoute !== null) {
        $links[] = [
            'label' => 'Aufgabenverlauf',
            'description' => 'Bisherige Aufgaben',
            'href' => route($historyRoute),
            'active' => request()->routeIs($historyRoute),
        ];
    }
@endphp

<aside class="lg:sticky lg:top-20 lg:self-start">
    <nav class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm" aria-label="Aufgabenbereich">
        <div class="px-3 pb-3 pt-2">
            <p class="font-heading text-sm font-semibold text-slate-950">Aufgabenbereich</p>
            <p class="mt-1 text-xs text-slate-500">Wähle einen Übungstyp.</p>
        </div>

        <div class="space-y-1">
            @foreach($links as $link)
                <a
                    href="{{ $link['href'] }}"
                    @class([
                        'group flex items-start gap-3 rounded-md border px-3 py-3 transition',
                        'border-slate-900 bg-slate-900 text-white shadow-sm' => $link['active'],
                        'border-transparent text-slate-700 hover:border-slate-200 hover:bg-slate-50 hover:text-slate-950' => ! $link['active'],
                    ])
                >
                    <span
                        @class([
                            'mt-1 h-2 w-2 shrink-0 rounded-full',
                            'bg-white' => $link['active'],
                            'bg-slate-300 group-hover:bg-slate-500' => ! $link['active'],
                        ])
                    ></span>
                    <span class="min-w-0">
                        <span class="block font-heading text-sm font-semibold">{{ $link['label'] }}</span>
                        <span
                            @class([
                                'mt-0.5 block text-xs',
                                'text-slate-200' => $link['active'],
                                'text-slate-500 group-hover:text-slate-600' => ! $link['active'],
                            ])
                        >
                            {{ $link['description'] }}
                        </span>
                    </span>
                </a>
            @endforeach
        </div>
    </nav>
</aside>
