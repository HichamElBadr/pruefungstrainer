@props([
    'title',
    'description' => null,
])

<x-app-layout>
    <div class="min-h-[calc(100vh-4rem)] bg-slate-50">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[16rem_minmax(0,1fr)] lg:px-8">
            @include('it.partials.exercise-sidebar')

            <div class="min-w-0 space-y-6">
                <header class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="max-w-3xl">
                            <p class="font-heading text-xs font-semibold uppercase text-slate-500">Prüfungstrainer</p>
                            <h1 class="mt-2 font-heading text-2xl font-bold text-slate-950 sm:text-3xl">{{ $title }}</h1>

                            @if($description)
                                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $description }}</p>
                            @endif
                        </div>

                        @isset($badges)
                            <div class="flex flex-wrap gap-2">
                                {{ $badges }}
                            </div>
                        @endisset
                    </div>
                </header>

                {{ $slot }}
            </div>
        </div>
    </div>
</x-app-layout>
