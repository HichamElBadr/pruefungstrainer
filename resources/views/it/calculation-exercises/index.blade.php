<x-exercise-layout
    title="Rechenaufgaben"
    description="Trainiere typische IT-Rechenwege und prüfe dein Ergebnis direkt im Browser."
>
    <x-slot name="badges">
        <span class="exercise-badge">Rechenaufgabe</span>
        <span class="exercise-badge">Themenauswahl</span>
        @if(!empty($sourceLabel))
            <span class="exercise-badge">{{ $sourceLabel }}</span>
        @endif
        @if(!empty($difficultyLabel))
            <span class="exercise-badge">Schwierigkeit: {{ $difficultyLabel }}</span>
        @endif
    </x-slot>

    @if($errors->has('exercise_source'))
        <div class="exercise-alert exercise-alert-error">
            <p class="font-heading font-semibold">Aufgabe konnte nicht geladen werden</p>
            <p class="mt-1">{{ $errors->first('exercise_source') }}</p>
        </div>
    @endif

    @php
        $topicDescriptions = [
            'prozentrechnung' => 'Rabatte, Preisänderungen und Prozentwerte berechnen.',
            'dreisatz' => 'Verhältnisse und proportionale Zusammenhänge lösen.',
            'multiplikation' => 'Zahlen sicher multiplizieren und typische IT-Rechenwege üben.',
            'division' => 'Teilungen, Anteile und einfache Verteilungen berechnen.',
            'speichergroessen' => 'Byte, KB, MB, GB und TB sicher umrechnen.',
            'stromverbrauch' => 'Leistung, Laufzeit, Energieverbrauch und Kosten berechnen.',
            'hardwarekosten' => 'Komponentenpreise, Gesamtkosten und Budgets berechnen.',
        ];
    @endphp

    @if(!empty($task))
        <section class="exercise-card">
            <div class="exercise-card-body">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        @if(!empty($selectedTopic))
                            <p class="font-heading text-sm font-semibold text-slate-500">{{ $selectedTopic['label'] }}</p>
                        @endif

                        <h2 class="mt-1 font-heading text-xl font-semibold text-slate-950">
                            {{ $title ?? 'Rechenaufgabe' }}
                        </h2>
                    </div>

                    @if(!empty($sourceLabel))
                        <span class="exercise-badge">{{ $sourceLabel }}</span>
                    @endif
                </div>

                <div class="exercise-muted-panel mt-5 whitespace-pre-line leading-7">
                    {{ $task }}
                </div>
            </div>
        </section>

        <section class="exercise-card">
            <div class="exercise-card-body">
                <h2 class="font-heading text-xl font-semibold text-slate-950">Deine Antwort</h2>

                <form action="{{ route('calculation-exercises.check') }}" method="POST" class="mt-4">
                    @csrf

                    <label for="user_solution" class="font-heading block text-sm font-semibold text-slate-800">Ergebnis</label>
                    <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                        <input
                            id="user_solution"
                            name="user_solution"
                            type="text"
                            value="{{ old('user_solution', $user_solution ?? '') }}"
                            class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            placeholder="Dein Ergebnis"
                        >

                        @if(!empty($unit))
                            <div class="inline-flex min-h-10 items-center rounded-lg border border-slate-200 bg-slate-50 px-4 text-sm font-semibold text-slate-700">
                                Einheit: {{ $unit }}
                            </div>
                        @endif
                    </div>
                    <x-input-error :messages="$errors->get('user_solution')" class="mt-2" />

                    <button type="submit" class="exercise-button mt-4">Lösung prüfen</button>
                </form>
            </div>
        </section>

        @if(isset($is_correct))
            <section class="exercise-card">
                <div class="exercise-card-body">
                    @if($is_correct)
                        <div class="exercise-alert exercise-alert-success">
                            <p class="font-heading font-semibold">Deine Lösung ist korrekt.</p>
                        </div>
                    @else
                        <div class="exercise-alert exercise-alert-error">
                            <p class="font-heading font-semibold">Deine Lösung ist leider falsch.</p>
                            <p class="mt-1">Vergleiche dein Ergebnis mit dem erwarteten Ergebnis und der Musterlösung.</p>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        @if(!empty($expected_value))
            <section class="exercise-card">
                <div class="exercise-card-body">
                    <h2 class="font-heading text-xl font-semibold text-slate-950">Erwartetes Ergebnis</h2>
                    <div class="exercise-muted-panel mt-4 text-lg font-semibold">
                        {{ $expected_value }}@if(!empty($unit)) {{ $unit }}@endif
                    </div>
                </div>
            </section>
        @endif

        @if(!empty($solution_steps))
            <section class="exercise-card">
                <div class="exercise-card-body">
                    <h2 class="font-heading text-xl font-semibold text-slate-950">Musterlösung</h2>
                    <div class="exercise-muted-panel mt-4 whitespace-pre-line leading-7">
                        {{ $solution_steps }}
                    </div>
                </div>
            </section>
        @endif
    @endif

    <section class="exercise-card">
        <div class="exercise-card-body">
            <div>
                <h2 class="font-heading text-xl font-semibold text-slate-950">
                    @if(!empty($task))
                        Weitere Aufgabe erzeugen
                    @else
                        Thema auswählen
                    @endif
                </h2>
                <p class="mt-2 text-sm text-slate-600">Wähle ein Thema, um eine neue Rechenaufgabe zu starten.</p>
            </div>

            <div class="mt-5 flex flex-wrap gap-2">
                @foreach($difficulties as $difficulty)
                    <a
                        href="{{ route('calculation-exercises.index', ['difficulty' => $difficulty['value']]) }}"
                        class="{{ $selectedDifficulty === $difficulty['value'] ? 'exercise-button' : 'exercise-button-secondary' }}"
                    >
                        {{ $difficulty['label'] }}
                    </a>
                @endforeach
            </div>

            <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($topics as $topic)
                    <form method="POST" action="{{ route('calculation-exercises.generate', $topic['slug']) }}">
                        @csrf
                        <input type="hidden" name="difficulty" value="{{ $selectedDifficulty }}">
                        <button
                            type="submit"
                            class="group flex min-h-40 w-full flex-col justify-between rounded-lg border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:border-slate-400 hover:bg-slate-50 hover:shadow focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
                        >
                            <span>
                                <span class="font-heading block text-base font-semibold text-slate-950">{{ $topic['label'] }}</span>
                                <span class="mt-3 block text-sm leading-6 text-slate-600">
                                    {{ $topicDescriptions[$topic['slug']] ?? 'Neue Rechenaufgabe zu diesem Thema starten.' }}
                                </span>
                            </span>
                            <span class="mt-5 inline-flex w-fit items-center rounded-md bg-indigo-600 px-3 py-2 font-heading text-sm font-semibold text-white transition group-hover:bg-indigo-700">
                                Neue Aufgabe erzeugen
                            </span>
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </section>
</x-exercise-layout>
