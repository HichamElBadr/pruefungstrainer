<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            SQL-Aufgaben
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <section>
                <h3 class="text-lg font-semibold text-gray-900">SQL-Aufgaben</h3>
                <p class="mt-2 text-sm text-gray-600">Wähle einen Schwierigkeitsgrad aus.</p>

                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                    @foreach($difficulties as $difficulty)
                        <form method="POST" action="{{ route('sql-uebung.generate', $difficulty['value']) }}">
                            @csrf
                            <button
                                type="submit"
                                class="w-full min-h-24 rounded border border-gray-200 bg-white px-4 py-4 text-center shadow-sm transition hover:border-indigo-300 hover:shadow focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                <span class="block text-base font-semibold text-gray-900">{{ $difficulty['label'] }}</span>
                            </button>
                        </form>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
