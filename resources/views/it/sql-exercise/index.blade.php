<x-exercise-layout
    title="SQL-Übungsaufgabe"
    description="Lies die Aufgabenstellung, prüfe die Tabellen und führe deine SELECT-Abfrage gegen die Übungsdatenbank aus."
>
    <x-slot name="badges">
        <span class="exercise-badge">SQL</span>
        <span class="exercise-badge">Übungsaufgabe</span>
        @if(!empty($sourceLabel))
            <span class="exercise-badge">{{ $sourceLabel }}</span>
        @endif
        @if(!empty($difficultyLabel))
            <span class="exercise-badge">Schwierigkeit: {{ $difficultyLabel }}</span>
        @endif
    </x-slot>

    <section class="exercise-card">
        <div class="exercise-card-body">
            <h2 class="font-heading text-xl font-semibold text-slate-950">Aufgabenstellung</h2>
            <div class="exercise-muted-panel mt-4 leading-7">
                {{ $task }}
            </div>
        </div>
    </section>

    @if(!empty($tables))
        <section class="exercise-card">
            <div class="exercise-card-body space-y-5">
                <div>
                    <h2 class="font-heading text-xl font-semibold text-slate-950">Datenbasis</h2>
                    <p class="mt-2 text-sm text-slate-600">Nutze diese Tabellen und Beispieldaten für deine Abfrage.</p>
                </div>

                <div class="space-y-6">
                    @foreach($tables as $tableName => $rows)
                        <div>
                            <h3 class="mb-3 font-heading text-base font-semibold text-slate-900">{{ $tableName }}</h3>

                            @if(count($rows) > 0)
                                <div class="exercise-table-wrap">
                                    <table class="exercise-table">
                                        <thead>
                                            <tr>
                                                @foreach(array_keys($rows[0]) as $column)
                                                    <th scope="col">{{ $column }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($rows as $row)
                                                <tr>
                                                    @foreach($row as $value)
                                                        <td>{{ $value }}</td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-600">Keine Beispieldaten vorhanden.</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="exercise-card">
        <div class="exercise-card-body">
            <form method="POST" action="{{ route('sql-uebung.execute', $exerciseId) }}">
                @csrf

                <label for="sql_input" class="font-heading block text-base font-semibold text-slate-950">Deine SQL-Abfrage</label>
                <textarea
                    id="sql_input"
                    name="sql_input"
                    rows="6"
                    class="mt-3 block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                    placeholder="Gib hier deine SELECT-Abfrage ein..."
                >{{ $userSql ?? '' }}</textarea>
                <x-input-error :messages="$errors->get('sql_input')" class="mt-2" />

                @if(isset($result) && !$result['success'])
                    <div class="exercise-alert exercise-alert-error mt-4">
                        <p class="font-heading font-semibold">SQL-Fehler</p>
                        <p class="mt-2">{{ $result['message'] }}</p>

                        <p class="mt-4 text-sm font-semibold">Originale Datenbankmeldung:</p>
                        <pre class="exercise-code mt-2 overflow-x-auto whitespace-pre-wrap"><code>{{ $result['technical_message'] }}</code></pre>

                        <p class="mt-4 text-sm font-semibold">Hinweis:</p>
                        <p class="mt-1">{{ $result['hint'] }}</p>
                    </div>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button type="submit" class="exercise-button">Ausführen</button>
                    <p class="text-sm text-slate-500">Erlaubt sind sichere SELECT-Abfragen.</p>
                </div>
            </form>
        </div>
    </section>

    @if(isset($result) && $result['success'])
        <section class="exercise-card">
            <div class="exercise-card-body">
                <h2 class="font-heading text-xl font-semibold text-slate-950">Ergebnis</h2>

                <div class="mt-4 space-y-6">
                    <div>
                        <h3 class="mb-3 font-heading text-base font-semibold text-slate-900">Deine Ausgabe</h3>
                        @include('it.sql-exercise.partials.query-result-table', [
                            'queryResult' => $result,
                        ])
                    </div>

                    @if(isset($solutionResult) && $solutionResult['success'])
                        <div>
                            <h3 class="mb-3 font-heading text-base font-semibold text-slate-900">Erwartete Ausgabe</h3>
                            @include('it.sql-exercise.partials.query-result-table', [
                                'queryResult' => $solutionResult,
                            ])
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    <section class="exercise-card">
        <div class="exercise-card-body">
            <details>
                <summary class="cursor-pointer font-heading text-base font-semibold text-slate-950">Musterlösung (SQL) anzeigen</summary>
                <pre class="exercise-code mt-4 overflow-x-auto"><code>{{ $solution }}</code></pre>
                @if(!empty($explanation))
                    <div class="exercise-muted-panel mt-4 whitespace-pre-line leading-7">
                        {{ $explanation }}
                    </div>
                @endif
            </details>
        </div>
    </section>

    <div class="flex justify-end">
        <form method="POST" action="{{ route('sql-uebung.next', $exerciseId) }}">
            @csrf
            <button type="submit" class="exercise-button">
                Nächste Aufgabe &rarr;
            </button>
        </form>
    </div>
</x-exercise-layout>
