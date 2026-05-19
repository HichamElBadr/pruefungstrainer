<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Rechenaufgaben
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <section>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Thema auswählen</h3>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($topics as $topic)
                        <form method="POST" action="{{ route('calculation-exercises.generate', $topic['slug']) }}">
                            @csrf
                            <button
                                type="submit"
                                class="w-full min-h-24 rounded border border-gray-200 bg-white px-4 py-4 text-left shadow-sm transition hover:border-indigo-300 hover:shadow focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                <span class="block text-base font-semibold text-gray-900">{{ $topic['label'] }}</span>
                                <span class="mt-2 block text-sm text-gray-600">Neue Aufgabe erzeugen</span>
                            </button>
                        </form>
                    @endforeach
                </div>
            </section>

            @if(!empty($generated_task))
                <section class="rounded border border-gray-200 bg-white p-6 shadow-sm">
                    @if(!empty($selectedTopic))
                        <p class="mb-2 text-sm font-medium text-gray-600">{{ $selectedTopic['label'] }}</p>
                    @endif

                    @if(!empty($sourceLabel))
                        <span class="mb-3 inline-flex rounded border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">
                            {{ $sourceLabel }}
                        </span>
                    @endif

                    @if(!empty($title))
                        <h3 class="mb-4 text-xl font-semibold text-gray-900">{{ $title }}</h3>
                    @endif

                    <h4 class="mb-3 text-lg font-semibold text-gray-900">Aufgabe</h4>
                    <div class="rounded border border-gray-200 bg-gray-50 p-4 text-gray-900">
                        {{ $generated_task }}
                    </div>

                    <form action="{{ route('calculation-exercises.check') }}" method="POST" class="mt-6">
                        @csrf
                        <div>
                            <label for="user_solution" class="block text-sm font-semibold text-gray-800">Deine Lösung</label>
                            <input
                                id="user_solution"
                                name="user_solution"
                                type="text"
                                value="{{ old('user_solution') }}"
                                class="mt-2 block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                            @if(!empty($expected_unit))
                                <p class="mt-2 text-sm text-gray-600">Einheit: {{ $expected_unit }}</p>
                            @endif
                            <x-input-error :messages="$errors->get('user_solution')" class="mt-2" />
                        </div>

                        <button
                            type="submit"
                            class="mt-4 rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            Lösung prüfen
                        </button>
                    </form>

                    @if(isset($is_correct))
                        <div class="mt-6">
                            @if($is_correct)
                                <div class="font-semibold text-green-700">Deine Lösung ist korrekt.</div>
                            @else
                                <div class="font-semibold text-red-700">Deine Lösung ist leider falsch.</div>
                            @endif
                        </div>
                    @endif

                    @if(!empty($solution))
                        <div class="mt-6">
                            <h3 class="mb-2 text-base font-semibold text-gray-900">Erwartetes Ergebnis</h3>
                            <div class="rounded border border-gray-200 bg-gray-50 p-4 text-gray-900">
                                {{ $solution }}@if(!empty($expected_unit)) {{ $expected_unit }}@endif
                            </div>
                        </div>
                    @endif

                    @if(!empty($sample_solution))
                        <div class="mt-6">
                            <h3 class="mb-2 text-base font-semibold text-gray-900">Musterlösung</h3>
                            <div class="whitespace-pre-line rounded border border-gray-200 bg-gray-50 p-4 text-gray-900">{{ $sample_solution }}</div>
                        </div>
                    @endif
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
