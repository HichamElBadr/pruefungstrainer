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
