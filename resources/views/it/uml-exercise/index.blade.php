<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-800">
            UML
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto py-6">
        @if(!empty($error))
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">
            {{ $error }}
        </div>
        @endif

        <form action="{{ route('uml.render') }}" method="POST" class="space-y-3">
            @csrf
            <label for="uml_text" class="block text-sm text-gray-700">UML (vereinfachter Text)</label>
            <textarea id="uml_text" name="uml_text" rows="10"
                class="w-full border rounded p-2 font-mono text-sm"
                placeholder="class Person&#10;  - name : String&#10;  + getName() : String">{{ old('uml_text', $input ?? '') }}</textarea>

            <button type="submit"
                class="px-4 py-2 rounded bg-gray-800 text-white">
                Diagramm generieren
            </button>
        </form>

        @if(!empty($imageDataUrl))
        <div class="mt-6">
            <h3 class="text-sm text-gray-600 mb-2">Vorschau:</h3>
            <img src="{{ $imageDataUrl }}" alt="UML Diagramm" class="max-w-full border rounded">
        </div>
        @endif

        
        {{-- How to use --}}
        <div class="mt-6 border rounded p-4 bg-white">
            <h3 class="font-semibold mb-2">How to use (Kurz-Anleitung)</h3>
            <ul class="list-disc pl-6 space-y-1 text-sm text-gray-800">
                <li><strong>Klasse beginnen:</strong> <code class="font-mono">class Klassenname</code></li>
                <li><strong>Mitglieder einrücken:</strong> jede Attribut-/Methodenzeile mit zwei Leerzeichen beginnen.</li>
                <li><strong>Sichtbarkeit:</strong> <code class="font-mono">+</code> public, <code class="font-mono">-</code> private, <code class="font-mono">#</code> protected.</li>
                <li><strong>Attribute:</strong> <code class="font-mono">- name : String</code> (Typ optional).</li>
                <li><strong>Methoden:</strong> <code class="font-mono">+ getName() : String</code> (Rückgabetyp optional).</li>
                <li><strong>Mehrere Klassen:</strong> Klassenblöcke durch eine <em>Leerzeile</em> trennen.</li>
                <li><strong>Beziehungen (optional):</strong>
                    <code class="font-mono">A -> B : label</code>,
                    <code class="font-mono">A -- B</code>,
                    <code class="font-mono">A o-- B</code> (Aggregation),
                    <code class="font-mono">A *-- B</code> (Komposition),
                    <code class="font-mono">A ..> B</code> (Dependency),
                    <code class="font-mono">Parent &lt;|-- Child</code> (Vererbung).
                </li>
                <li><strong>Schon PlantUML?</strong> Du kannst auch direkt einen kompletten Block mit <code class="font-mono">@startuml ... @enduml</code> einfügen.</li>
            </ul>

            <div class="mt-3">
                <div class="text-xs text-gray-600 mb-1">Beispiel-Eingabe:</div>
                <pre class="border rounded p-3 bg-gray-50 text-sm overflow-auto"><code>
                class Person
                - name : String
                - age  : Integer
                + getName() : String

                class Hund
                - rasse : String
                + bellen() : void

                Person -> Hund : besitzt</code></pre>
                <p class="text-xs text-gray-600 mt-2">
                    Tipp: Achte auf eine <em>Leerzeile zwischen Klassen</em>, damit der Parser den Klassenblock sauber schließt.
                </p>
            </div>
        </div>
    </div>

</x-app-layout>