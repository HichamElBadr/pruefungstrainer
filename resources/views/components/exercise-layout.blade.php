@props([
    'title',
    'description' => null,
    'compact' => false,
    'narrow' => false,
])

<x-app-layout>
    <div class="min-h-screen bg-slate-50">
        <div
            @class([
                'mx-auto grid w-full gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[17rem_minmax(0,1fr)] lg:px-8',
                'max-w-7xl' => $narrow,
                'max-w-[100rem]' => ! $narrow,
            ])
            data-exercise-shell
            data-layout-width="{{ $narrow ? 'narrow' : 'wide' }}"
        >
            @include('it.partials.exercise-sidebar')

            <div @class([
                'min-w-0',
                'space-y-4' => $compact,
                'space-y-6' => ! $compact,
            ])>
                <header @class([
                    'rounded-lg border border-slate-200 bg-white shadow-sm',
                    'p-4 sm:px-5 sm:py-4' => $compact,
                    'p-5 sm:p-6' => ! $compact,
                ])>
                    <div @class([
                        'flex flex-col lg:flex-row lg:items-start lg:justify-between',
                        'gap-3' => $compact,
                        'gap-4' => ! $compact,
                    ])>
                        <div class="max-w-3xl">
                            <p class="font-heading text-xs font-semibold uppercase text-slate-500">Prüfungstrainer</p>
                            <h1 @class([
                                'font-heading font-bold text-slate-950',
                                'mt-1 text-2xl' => $compact,
                                'mt-2 text-2xl sm:text-3xl' => ! $compact,
                            ])>{{ $title }}</h1>

                            @if($description)
                                <p @class([
                                    'text-sm text-slate-600',
                                    'mt-1 leading-5' => $compact,
                                    'mt-3 leading-6' => ! $compact,
                                ])>{{ $description }}</p>
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
