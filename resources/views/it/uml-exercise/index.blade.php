<x-exercise-layout
    title="UML-Aufgaben"
    description="Bearbeite unterschiedliche UML- und Datenmodellierungsaufgaben direkt mit PlantUML."
>
    <x-slot name="badges">
        <span class="exercise-badge">UML</span>
        @if(!empty($sourceLabel))
            <span class="exercise-badge">{{ $sourceLabel }}</span>
        @endif
        @if(!empty($difficultyLabel))
            <span class="exercise-badge">Schwierigkeit: {{ $difficultyLabel }}</span>
        @endif
        @if(!empty($diagramTypeLabel))
            <span class="exercise-badge">{{ $diagramTypeLabel }}</span>
        @endif
        <span class="exercise-badge">Übungsaufgabe</span>
    </x-slot>

    @if(!empty($error))
        <div class="exercise-alert exercise-alert-error">
            <p class="font-heading font-semibold">Aufgabe konnte nicht verarbeitet werden</p>
            <p class="mt-1">{{ $error }}</p>
        </div>
    @endif

    <section class="exercise-card">
        <div class="exercise-card-body">
            <h2 class="font-heading text-lg font-semibold text-slate-950">Diagrammtyp auswählen</h2>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($diagramTypes as $diagramType)
                    <a
                        href="{{ route('uml.form', ['diagram_type' => $diagramType['value']]) }}"
                        class="{{ $selectedDiagramType === $diagramType['value'] ? 'exercise-button' : 'exercise-button-secondary' }}"
                    >
                        {{ $diagramType['label'] }}
                    </a>
                @endforeach
            </div>

            <h3 class="mt-6 font-heading text-base font-semibold text-slate-950">Schwierigkeit auswählen</h3>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach($difficulties as $difficulty)
                    <a
                        href="{{ route('uml.form', ['difficulty' => $difficulty['value']]) }}"
                        class="{{ $selectedDifficulty === $difficulty['value'] ? 'exercise-button' : 'exercise-button-secondary' }}"
                    >
                        {{ $difficulty['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    @if(!empty($exercise))
        <section class="exercise-card">
            <div class="exercise-card-body">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-heading text-xl font-semibold text-slate-950">
                            {{ $exercise['title'] }}
                        </h2>
                        <p class="mt-2 text-sm text-slate-600">
                            {{ $diagramTypeLabel }} · {{ $difficultyLabel }}
                            @if(!empty($exercise['topic']))
                                · Thema: {{ $exercise['topic'] }}
                            @endif
                        </p>
                    </div>
                </div>

                @if(!empty($exercise['scenario']))
                    <div class="exercise-muted-panel mt-5">
                        <h3 class="font-heading text-base font-semibold text-slate-950">Szenario</h3>
                        <p class="mt-2 whitespace-pre-line leading-7">{{ $exercise['scenario'] }}</p>
                    </div>
                @endif

                @if(!empty($exercise['requirements']))
                    <div class="mt-5">
                        <h3 class="font-heading text-base font-semibold text-slate-950">Anforderungen</h3>
                        <ul class="mt-3 list-disc space-y-2 pl-5 leading-7 text-slate-800">
                            @foreach($exercise['requirements'] as $requirement)
                                <li>{{ $requirement }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-5">
                    <h3 class="font-heading text-base font-semibold text-slate-950">Aufgabe</h3>
                    <div class="exercise-muted-panel mt-3 whitespace-pre-line leading-7">
                        {{ $exercise['task'] }}
                    </div>
                </div>
            </div>
        </section>

        <x-exercise.hints :hints="$exercise['hints'] ?? []" />

        <section class="exercise-card">
            <div class="exercise-card-body">
                <h3 class="font-heading text-lg font-semibold text-slate-950">PlantUML-Eingabe</h3>

                <form action="{{ route('uml.render') }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="exercise_id" value="{{ $exercise['database_id'] }}">

                    <div>
                        <label for="uml_text" class="font-heading block text-sm font-semibold text-slate-800">
                            PlantUML-Code
                        </label>
                        <textarea
                            id="uml_text"
                            name="uml_text"
                            rows="16"
                            class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            placeholder="@startuml&#10;...&#10;@enduml"
                        >{{ old('uml_text', $input) }}</textarea>
                        <x-input-error :messages="$errors->get('uml_text')" class="mt-2" />
                    </div>

                    <button type="submit" class="exercise-button">Diagramm rendern</button>
                </form>
            </div>
        </section>
    @endif

    @if(!empty($imageDataUrl))
        <section class="exercise-card">
            <div class="exercise-card-body">
                <h2 class="font-heading text-xl font-semibold text-slate-950">Dein Diagramm</h2>
                <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <img
                        src="{{ $imageDataUrl }}"
                        alt="Gerendertes UML-Diagramm"
                        class="max-w-full rounded-md border border-slate-200 bg-white"
                    >
                </div>
            </div>
        </section>
    @endif

    @if(!empty($exercise['solution_plantuml']))
        <section class="exercise-card">
            <div class="exercise-card-body">
                <details>
                    <summary class="cursor-pointer font-heading text-base font-semibold text-slate-950">
                        Musterlösung anzeigen
                    </summary>

                    <pre class="exercise-code mt-4 overflow-x-auto"><code>{{ $exercise['solution_plantuml'] }}</code></pre>

                    @if(!empty($exercise['expected_elements']))
                        <h3 class="mt-5 font-heading text-base font-semibold text-slate-950">Erwartete Elemente</h3>
                        <ul class="mt-3 list-disc space-y-2 pl-5 text-slate-800">
                            @foreach($exercise['expected_elements'] as $element)
                                <li>{{ $element }}</li>
                            @endforeach
                        </ul>
                    @endif

                    @if(!empty($exercise['explanation']))
                        <div class="exercise-muted-panel mt-5 whitespace-pre-line leading-7">
                            {{ $exercise['explanation'] }}
                        </div>
                    @endif
                </details>
            </div>
        </section>
    @endif

    <section class="exercise-card">
        <div class="exercise-card-body">
            <h2 class="font-heading text-xl font-semibold text-slate-950">PlantUML-Hinweis</h2>
            <p class="mt-3 text-sm leading-6 text-slate-700">
                Vollständiger PlantUML-Code mit <code>@startuml</code> wird unverändert gerendert.
                Fehlen die Start- und Endmarkierungen, ergänzt die Anwendung sie automatisch.
                Die konkrete Syntax richtet sich nach dem ausgewählten Diagrammtyp.
            </p>
        </div>
    </section>
</x-exercise-layout>
