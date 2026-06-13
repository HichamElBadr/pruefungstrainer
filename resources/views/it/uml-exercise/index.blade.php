<x-exercise-layout
    title="UML-Aufgaben"
    description="Erstelle aus vereinfachter Texteingabe ein UML-Klassendiagramm und prüfe die Darstellung direkt als Vorschau."
>
    <x-slot name="badges">
        <span class="exercise-badge">UML</span>
        @if(!empty($sourceLabel))
            <span class="exercise-badge">{{ $sourceLabel }}</span>
        @endif
        @if(!empty($difficultyLabel))
            <span class="exercise-badge">Schwierigkeit: {{ $difficultyLabel }}</span>
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
            <h2 class="font-heading text-xl font-semibold text-slate-950">
                {{ $exercise['title'] ?? 'UML-Aufgabe' }}
            </h2>

            @if(!empty($exercise['task']))
                <div class="exercise-muted-panel mt-4 whitespace-pre-line leading-7">
                    {{ $exercise['task'] }}
                </div>
            @endif

            <div class="mt-5 flex flex-wrap gap-2">
                @foreach($difficulties as $difficulty)
                    <a
                        href="{{ route('uml.form', ['difficulty' => $difficulty['value']]) }}"
                        class="{{ $selectedDifficulty === $difficulty['value'] ? 'exercise-button' : 'exercise-button-secondary' }}"
                    >
                        {{ $difficulty['label'] }}
                    </a>
                @endforeach
            </div>

            <h3 class="mt-7 font-heading text-lg font-semibold text-slate-950">UML-Eingabe</h3>

            <form action="{{ route('uml.render') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="difficulty" value="{{ $selectedDifficulty }}">

                <div>
                    <label for="uml_text" class="font-heading block text-sm font-semibold text-slate-800">Vereinfachter UML-Text</label>
                    <textarea
                        id="uml_text"
                        name="uml_text"
                        rows="12"
                        class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        placeholder="class Person&#10;  - name : String&#10;  + getName() : String"
                    >{{ old('uml_text', $input ?? '') }}</textarea>
                    <x-input-error :messages="$errors->get('uml_text')" class="mt-2" />
                </div>

                <button type="submit" class="exercise-button">Diagramm generieren</button>
            </form>
        </div>
    </section>

    @if(!empty($imageDataUrl))
        <section class="exercise-card">
            <div class="exercise-card-body">
                <h2 class="font-heading text-xl font-semibold text-slate-950">Vorschau</h2>
                <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <img src="{{ $imageDataUrl }}" alt="UML-Diagramm" class="max-w-full rounded-md border border-slate-200 bg-white">
                </div>
            </div>
        </section>
    @endif

    @if(!empty($exercise['solution_plantuml']))
        <section class="exercise-card">
            <div class="exercise-card-body">
                <details>
                    <summary class="cursor-pointer font-heading text-base font-semibold text-slate-950">Musterloesung anzeigen</summary>
                    <pre class="exercise-code mt-4 overflow-x-auto"><code>{{ $exercise['solution_plantuml'] }}</code></pre>
                    @if(!empty($exercise['explanation']))
                        <div class="exercise-muted-panel mt-4 whitespace-pre-line leading-7">
                            {{ $exercise['explanation'] }}
                        </div>
                    @endif
                </details>
            </div>
        </section>
    @endif

    <section class="exercise-card">
        <div class="exercise-card-body">
            <h2 class="font-heading text-xl font-semibold text-slate-950">Syntaxhilfe</h2>

            <div class="mt-4 grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(18rem,0.9fr)]">
                <ul class="space-y-2 text-sm leading-6 text-slate-700">
                    <li><span class="font-semibold text-slate-900">Klasse:</span> <code>class Klassenname</code></li>
                    <li><span class="font-semibold text-slate-900">Mitglieder:</span> Attribute und Methoden stehen unter der Klasse.</li>
                    <li><span class="font-semibold text-slate-900">Sichtbarkeit:</span> <code>+</code> public, <code>-</code> private, <code>#</code> protected.</li>
                    <li><span class="font-semibold text-slate-900">Attribute:</span> <code>- name : String</code></li>
                    <li><span class="font-semibold text-slate-900">Methoden:</span> <code>+ getName() : String</code></li>
                    <li><span class="font-semibold text-slate-900">Beziehungen:</span> <code>A -&gt; B : label</code>, <code>A o-- B</code>, <code>Parent &lt;|-- Child</code></li>
                </ul>

                <div>
                    <p class="mb-2 font-heading text-sm font-semibold text-slate-800">Beispiel-Eingabe</p>
                    <pre class="exercise-code overflow-x-auto"><code>class Person
- name : String
- age : Integer
+ getName() : String

class Hund
- rasse : String
+ bellen() : void

Person -> Hund : besitzt</code></pre>
                </div>
            </div>
        </div>
    </section>
</x-exercise-layout>
