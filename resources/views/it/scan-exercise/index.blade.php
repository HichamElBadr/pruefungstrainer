<x-app-layout>
    <x-slot name="header">
        <h2 style="font-weight: bold; font-size: 1.5rem; color: #333;">
            Scan-Übungsaufgabe
        </h2>
    </x-slot>

    @if(!empty($generated_task))
    <div style="margin-top: 30px;">
        <h3 style="margin-bottom: 5px;">Aufgabe:</h3>
        <div style="background: #f7f7f7; padding: 15px; border: 1px solid #ccc; border-radius: 5px; font-family: monospace;">
            {!! $generated_task !!}
        </div>
    </div>
    @endif

    <form action="{{ route('scan-uebung') }}" method="POST" style="margin-top: 30px;">
        @csrf
        <div style="margin-bottom: 15px;">
            <label for="user_solution" style="font-weight: bold;">Deine Lösung:</label>
            <textarea id="user_solution" name="user_solution" rows="5" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-family: monospace;"></textarea>
        </div>
        <button type="submit" style="background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
            Lösung prüfen
        </button>
    </form>

    @if(!empty($solution))
    <div style="margin-top: 30px;">
        <h3 style="margin-bottom: 5px;">Musterlösung:</h3>
        <div style="background: #f7f7f7; padding: 15px; border: 1px solid #ccc; border-radius: 5px; font-family: monospace;">
            {!! $solution !!}
        </div>
    </div>
    @endif

    @if(isset($is_correct))
    <div style="margin-top: 20px;">
        @if($is_correct)
        <div style="color: green; font-weight: bold;">✅ Deine Lösung ist korrekt!</div>
        @else
        <div style="color: red; font-weight: bold;">❌ Deine Lösung ist leider falsch.</div>
        @endif
    </div>
    @endif
</x-app-layout>