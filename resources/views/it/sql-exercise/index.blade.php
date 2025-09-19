<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>


<h2>SQL-Übungsaufgabe</h2>
<!-- task bekommen   -->
<p>{{ $task }}</p>

@if(!empty($tables))
        @foreach($tables as $tableName => $rows)
            <h4 class="mt-4">{{ $tableName }}</h4>
            @if(count($rows) > 0)
                <table border="1" cellpadding="5" cellspacing="0">
                    <thead>
                        <tr>
                            @foreach(array_keys($rows[0]) as $column)
                                <th>{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                @foreach($row as $value)
                                    <td>{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p><em>(keine Daten vorhanden)</em></p>
            @endif
        @endforeach
    @endif

<form method="POST" action="{{ route('sql-uebung') }}">
    @csrf
    <textarea name="sql_input" rows="5" cols="60" placeholder="Gib hier deine SELECT-Abfrage ein...">{{ $userSql ?? '' }}</textarea>
    <br><br>
    <input type="submit" value="Ausführen">
</form>

@if(!empty($result))
    <h3>Ergebnis:</h3>
    {!! $result !!}
@endif

<details>
    <summary>Lösung anzeigen</summary>
    <pre>SELECT * FROM kunden WHERE land='Deutschland';</pre>
</details>
</x-app-layout>