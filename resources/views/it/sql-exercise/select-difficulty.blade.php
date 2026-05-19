<x-exercise-layout
    title="SQL-Aufgaben"
    description="Wähle eine Übungsstufe und starte eine neue SQL-Aufgabe mit passender Beispieldatenbank."
>
    <x-slot name="badges">
        <span class="exercise-badge">SQL</span>
        <span class="exercise-badge">Übungsaufgabe</span>
    </x-slot>

    @php
        $difficultyDescriptions = [
            'easy' => 'Grundlagen mit SELECT, WHERE und ORDER BY.',
            'medium' => 'Abfragen mit JOINs, mehreren Tabellen und Bedingungen.',
            'hard' => 'Komplexere Aufgaben mit GROUP BY, HAVING und Aggregationen.',
        ];
    @endphp

    <section class="exercise-card">
        <div class="exercise-card-body">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="font-heading text-xl font-semibold text-slate-950">Übungsstufe auswählen</h2>
                    <p class="mt-2 text-sm text-slate-600">Die Auswahl bestimmt Umfang, Tabellenstruktur und SQL-Konzepte der Aufgabe.</p>
                </div>
            </div>

            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                @foreach($difficulties as $difficulty)
                    <form method="POST" action="{{ route('sql-uebung.generate', $difficulty['value']) }}">
                        @csrf
                        <button
                            type="submit"
                            class="group flex min-h-40 w-full flex-col justify-between rounded-lg border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:border-slate-400 hover:bg-slate-50 hover:shadow focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
                        >
                            <span>
                                <span class="font-heading block text-lg font-semibold text-slate-950">{{ $difficulty['label'] }}</span>
                                <span class="mt-3 block text-sm leading-6 text-slate-600">
                                    {{ $difficultyDescriptions[$difficulty['value']] ?? 'Neue Aufgabe mit passender Beispieldatenbank.' }}
                                </span>
                            </span>
                            <span class="mt-5 inline-flex w-fit items-center rounded-md bg-indigo-600 px-3 py-2 font-heading text-sm font-semibold text-white transition group-hover:bg-indigo-700">
                                Aufgabe erzeugen
                            </span>
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </section>
</x-exercise-layout>
