@php
    $links = [
        [
            'label' => 'Dashboard',
            'description' => 'Überblick',
            'href' => route('dashboard'),
            'active' => request()->routeIs('dashboard'),
        ],
        [
            'label' => 'SQL-Aufgaben',
            'description' => 'Datenbanken abfragen',
            'href' => route('sql-uebung'),
            'active' => request()->routeIs('sql-uebung*') || request()->is('it/sql-uebung*'),
        ],
        [
            'label' => 'Rechenaufgaben',
            'description' => 'IT-Rechnen trainieren',
            'href' => route('calculation-exercises.index'),
            'active' => request()->routeIs('calculation-exercises.*') || request()->is('it/calculation-exercises*'),
        ],
        [
            'label' => 'UML-Aufgaben',
            'description' => 'Klassendiagramme üben',
            'href' => route('uml.form'),
            'active' => request()->routeIs('uml.*') || request()->is('it/uml*'),
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

<aside class="lg:sticky lg:top-6 lg:self-start">
    <nav class="flex flex-col rounded-lg border border-slate-200 bg-white p-3 shadow-sm lg:min-h-[calc(100vh-3rem)]" aria-label="Hauptnavigation">
        <div class="px-3 pb-5 pt-2">
            <p class="font-heading text-lg font-bold text-slate-950">Prüfungstrainer</p>
            <p class="mt-1 text-sm text-slate-500">IT-Aufgaben üben</p>
        </div>

        <div class="space-y-1 lg:flex-1">
            @foreach($links as $link)
                <a
                    href="{{ $link['href'] }}"
                    @class([
                        'group flex items-start gap-3 rounded-md border px-3 py-3 transition',
                        'border-indigo-600 bg-indigo-600 text-white shadow-sm' => $link['active'],
                        'border-transparent text-slate-700 hover:border-slate-200 hover:bg-slate-50 hover:text-slate-950' => ! $link['active'],
                    ])
                >
                    <span
                        @class([
                            'mt-1 h-2 w-2 shrink-0 rounded-full',
                            'bg-white' => $link['active'],
                            'bg-slate-300 group-hover:bg-indigo-500' => ! $link['active'],
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

        @auth
            <div class="mt-6 border-t border-slate-200 px-3 pt-4 lg:mt-auto">
                <p class="text-xs font-semibold uppercase text-slate-500">Angemeldet als</p>
                <p class="mt-1 truncate font-heading text-sm font-semibold text-slate-950">{{ auth()->user()->name }}</p>

                <div class="mt-3 grid gap-2">
                    @if(\Illuminate\Support\Facades\Route::has('profile.edit'))
                        <a
                            href="{{ route('profile.edit') }}"
                            @class([
                                'rounded-md border px-3 py-2 text-sm font-semibold transition',
                                'border-indigo-600 bg-indigo-50 text-indigo-700' => request()->routeIs('profile.edit'),
                                'border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950' => ! request()->routeIs('profile.edit'),
                            ])
                        >
                            Profil
                        </a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="w-full rounded-md border border-slate-200 px-3 py-2 text-left text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2"
                        >
                            Abmelden
                        </button>
                    </form>
                </div>
            </div>
        @endauth
    </nav>
</aside>
