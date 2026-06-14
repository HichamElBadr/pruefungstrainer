@props([
    'hints' => [],
    'mode' => 'practice',
])

@php
    $visibleHints = is_array($hints) ? $hints : [];
@endphp

@if($visibleHints !== [])
    <section class="exercise-card" data-hint-mode="{{ $mode }}">
        <div class="exercise-card-body">
            <h2 class="font-heading text-xl font-semibold text-slate-950">Hinweise</h2>

            <div class="mt-4 space-y-3">
                @foreach($visibleHints as $hint)
                    <details class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <summary class="cursor-pointer font-heading text-sm font-semibold text-slate-900">
                            Tipp {{ $hint['level'] }} anzeigen
                        </summary>

                        <div class="mt-3 border-t border-slate-200 pt-3">
                            <h3 class="font-heading text-sm font-semibold text-slate-950">
                                {{ $hint['title'] }}
                            </h3>
                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">
                                {{ $hint['text'] }}
                            </p>
                        </div>
                    </details>
                @endforeach
            </div>
        </div>
    </section>
@endif
