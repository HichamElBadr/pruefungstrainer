<x-app-layout>
    <x-slot name="header">
        <h2 style="font-weight: bold; font-size: 1.5rem; color: #333;">
            UML
        </h2>
    </x-slot>
    @if(!empty($task))
    <div style="margin-top: 30px;">
        <h3 style="margin-bottom: 5px;">Aufgabe:</h3>
        <div style="background: #f7f7f7; padding: 15px; border: 1px solid #ccc; border-radius: 5px; font-family: monospace;">
            {!! $task !!}
        </div>
    </div>
    @endif
</x-app-layout>