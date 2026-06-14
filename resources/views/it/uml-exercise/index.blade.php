<x-exercise-layout
    title="UML-Aufgaben"
    description="Bearbeite unterschiedliche UML- und Datenmodellierungsaufgaben direkt mit PlantUML."
    :compact="true"
>
    <x-slot name="badges">
        <span class="exercise-badge">UML</span>
        @if(!empty($sourceLabel))
            <span class="exercise-badge">{{ $sourceLabel }}</span>
        @endif
        <span class="exercise-badge">Übungsaufgabe</span>
    </x-slot>

    @if(!empty($error))
        <div class="exercise-alert exercise-alert-error">
            <p class="font-heading font-semibold">Aufgabe konnte nicht verarbeitet werden</p>
            <p class="mt-1">{{ $error }}</p>
        </div>
    @endif

    <section class="exercise-card" data-uml-toolbar>
        <div class="p-4 sm:px-5">
            <div class="grid gap-4 xl:grid-cols-2 xl:items-start">
                <div>
                    <h2 class="font-heading text-sm font-semibold text-slate-950">Diagrammtyp auswählen</h2>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($diagramTypes as $diagramType)
                            <a
                                href="{{ route('uml.form', ['diagram_type' => $diagramType['value']]) }}"
                                class="{{ $selectedDiagramType === $diagramType['value'] ? 'exercise-button' : 'exercise-button-secondary' }}"
                            >
                                {{ $diagramType['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <div>
                    <h2 class="font-heading text-sm font-semibold text-slate-950">Schwierigkeit auswählen</h2>
                    <div class="mt-2 flex flex-wrap gap-2">
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
            </div>
        </div>
    </section>

    @if(!empty($exercise))
        <div
            class="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] xl:items-start"
            data-uml-workspace
        >
            <div class="min-w-0 space-y-4" data-uml-task-column>
                <section class="exercise-card">
                    <div class="p-4 sm:p-5">
                        <h2 class="font-heading text-xl font-semibold text-slate-950">
                            {{ $exercise['title'] }}
                        </h2>

                        <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold text-slate-700">
                            @if(!empty($diagramTypeLabel))
                                <span class="exercise-badge">{{ $diagramTypeLabel }}</span>
                            @endif
                            @if(!empty($difficultyLabel))
                                <span class="exercise-badge">Schwierigkeit: {{ $difficultyLabel }}</span>
                            @endif
                            @if(!empty($exercise['topic']))
                                <span class="exercise-badge">Thema: {{ $exercise['topic'] }}</span>
                            @endif
                        </div>

                        @if(!empty($exercise['scenario']))
                            <div class="exercise-muted-panel mt-4">
                                <h3 class="font-heading text-base font-semibold text-slate-950">Szenario</h3>
                                <p class="mt-2 whitespace-pre-line leading-7">{{ $exercise['scenario'] }}</p>
                            </div>
                        @endif

                        @if(!empty($exercise['requirements']))
                            <div class="mt-4">
                                <h3 class="font-heading text-base font-semibold text-slate-950">Anforderungen</h3>
                                <ul class="mt-2 list-disc space-y-1.5 pl-5 leading-7 text-slate-800">
                                    @foreach($exercise['requirements'] as $requirement)
                                        <li>{{ $requirement }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="mt-4">
                            <h3 class="font-heading text-base font-semibold text-slate-950">Aufgabe</h3>
                            <div class="exercise-muted-panel mt-2 whitespace-pre-line leading-7">
                                {{ $exercise['task'] }}
                            </div>
                        </div>
                    </div>
                </section>

                <x-exercise.hints :hints="$exercise['hints'] ?? []" :compact="true" />

                @if(!empty($exercise['solution_plantuml']))
                    <section class="exercise-card">
                        <details class="group">
                            <summary class="cursor-pointer list-none p-4 font-heading text-base font-semibold text-slate-950 sm:px-5">
                                <span class="flex items-center justify-between gap-3">
                                    Musterlösung anzeigen
                                    <span
                                        aria-hidden="true"
                                        class="text-lg font-normal text-slate-400 transition group-open:rotate-45"
                                    >+</span>
                                </span>
                            </summary>

                            <div class="border-t border-slate-200 p-4 sm:p-5">
                                @if(!empty($solutionImageUrl))
                                    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-slate-50 p-3">
                                        <img
                                            src="{{ $solutionImageUrl }}"
                                            alt="Gerenderte UML-Musterlösung"
                                            class="mx-auto max-w-full rounded-md border border-slate-200 bg-white"
                                        >
                                    </div>
                                @endif

                                <pre class="exercise-code mt-4 overflow-x-auto"><code>{{ $exercise['solution_plantuml'] }}</code></pre>

                                @if(!empty($exercise['expected_elements']))
                                    <h3 class="mt-4 font-heading text-base font-semibold text-slate-950">Erwartete Elemente</h3>
                                    <ul class="mt-2 list-disc space-y-1.5 pl-5 text-slate-800">
                                        @foreach($exercise['expected_elements'] as $element)
                                            <li>{{ $element }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if(!empty($exercise['explanation']))
                                    <div class="exercise-muted-panel mt-4 whitespace-pre-line leading-7">
                                        {{ $exercise['explanation'] }}
                                    </div>
                                @endif
                            </div>
                        </details>
                    </section>
                @endif
            </div>

            <div class="min-w-0 space-y-4" data-uml-editor-column>
                <section class="exercise-card border-indigo-100 shadow-md">
                    <div class="p-4 sm:p-5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="font-heading text-lg font-semibold text-slate-950">PlantUML-Eingabe</h2>
                            <span class="text-xs font-medium text-slate-500">Editor</span>
                        </div>

                        <form action="{{ route('uml.render') }}" method="POST" class="mt-3">
                            @csrf
                            <input type="hidden" name="exercise_id" value="{{ $exercise['database_id'] }}">

                            <label for="uml_text" class="sr-only">PlantUML-Code</label>
                            <textarea
                                id="uml_text"
                                name="uml_text"
                                rows="16"
                                class="block min-h-80 w-full resize-y rounded-lg border-slate-300 bg-slate-950 text-sm leading-6 text-slate-50 shadow-inner focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="@startuml&#10;...&#10;@enduml"
                            >{{ old('uml_text', $input) }}</textarea>
                            <x-input-error :messages="$errors->get('uml_text')" class="mt-2" />

                            <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-xs leading-5 text-slate-500">
                                    Vollständiger PlantUML-Code mit <code>@startuml</code> wird unverändert gerendert.
                                    Fehlende Start- und Endmarkierungen ergänzt die Anwendung automatisch.
                                    Die konkrete Syntax richtet sich nach dem ausgewählten Diagrammtyp.
                                </p>
                                <button type="submit" class="exercise-button shrink-0">Diagramm rendern</button>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="exercise-card overflow-hidden" data-uml-preview>
                    <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 sm:px-5">
                        <h2 class="font-heading text-lg font-semibold text-slate-950">Dein Diagramm</h2>
                        @if(!empty($imageDataUrl))
                            <span class="text-xs font-medium text-emerald-700">Gerendert</span>
                        @endif
                    </div>

                    <div class="flex min-h-80 items-center justify-center overflow-auto bg-slate-50 p-4 sm:min-h-96 sm:p-5">
                        @if(!empty($imageDataUrl))
                            <img
                                src="{{ $imageDataUrl }}"
                                alt="Gerendertes UML-Diagramm"
                                class="max-h-[42rem] max-w-full rounded-md border border-slate-200 bg-white shadow-sm"
                            >
                        @else
                            <div class="max-w-sm text-center">
                                <p class="font-heading text-sm font-semibold text-slate-600">
                                    Diagrammvorschau
                                </p>
                                <p class="mt-1 text-sm leading-6 text-slate-500">
                                    Nach dem Rendern erscheint dein UML-Diagramm hier.
                                </p>
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    @endif
</x-exercise-layout>
