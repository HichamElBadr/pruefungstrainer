@if($queryResult['error'])
    <div class="exercise-alert exercise-alert-error">
        <p class="font-heading font-semibold">Hinweis zur Abfrage</p>
        <p class="mt-1">{{ $queryResult['error'] }}</p>
        @if(!empty($errorHint))
            <p class="mt-2 text-red-700">{{ $errorHint }}</p>
        @endif
    </div>
@else
    @if(!empty($queryResult['columns']))
        <div class="exercise-table-wrap">
            <table class="exercise-table">
                <thead>
                    <tr>
                        @foreach($queryResult['columns'] as $column)
                            <th scope="col">{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($queryResult['rows'] as $row)
                        <tr>
                            @foreach($queryResult['columns'] as $column)
                                <td>{{ $row[$column] ?? '' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($queryResult['message'])
        <div class="exercise-alert exercise-alert-info mt-4">
            {{ $queryResult['message'] }}
        </div>
    @elseif(empty($queryResult['columns']))
        <div class="exercise-alert exercise-alert-info">
            Keine Ausgabe verfügbar.
        </div>
    @endif
@endif
