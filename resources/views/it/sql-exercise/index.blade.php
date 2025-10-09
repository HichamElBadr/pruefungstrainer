<x-app-layout>
    <x-slot name="header">
        <h2 style="font-weight: bold; font-size: 1.5rem; color: #333;">
            Dashboard
        </h2>
    </x-slot>

    <div style="margin: 20px 0;">
        <h2 style="margin-bottom: 10px; color: #222;">SQL-Übungsaufgabe</h2>
        <p style="background: #f5f5f5; padding: 10px; border-left: 4px solid #007BFF;">{{ $task }}</p>
    </div>

    @if(!empty($tables))
        @foreach($tables as $tableName => $rows)
            <div style="margin-top: 30px;">
                <h4 style="margin-bottom: 5px; color: #444;">{{ $tableName }}</h4>
                @if(count($rows) > 0)
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                        <thead>
                            <tr style="background-color: #eee;">
                                @foreach(array_keys($rows[0]) as $column)
                                    <th style="border: 1px solid #ccc; padding: 8px; text-align: left;">{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    @foreach($row as $value)
                                        <td style="border: 1px solid #ccc; padding: 8px;">{{ $value }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="font-style: italic; color: #666;">(keine Daten vorhanden)</p>
                @endif
            </div>
        @endforeach
    @endif

    <div style="margin-top: 30px;">
        <form method="POST" action="{{ route('sql-uebung') }}">
            @csrf
            <label for="sql_input" style="display:block; margin-bottom:5px; font-weight: bold;">Deine SQL-Abfrage:</label>
            <textarea id="sql_input" name="sql_input" rows="5" style="width: 100%; padding: 8px; border: 1px solid #ccc;" placeholder="Gib hier deine SELECT-Abfrage ein...">{{ $userSql ?? '' }}</textarea>
            <br><br>
            <button type="submit" style="padding: 8px 15px; background-color: #007BFF; color: white; border: none; cursor: pointer;">Ausführen</button>
        </form>
    </div>

    @if(!empty($result))
        <div style="margin-top: 30px;">
            <h3>Ergebnis:</h3>
            <div style="background: #f9f9f9; padding: 10px; border: 1px solid #ddd;">{!! $result !!}</div>
        </div>
    @endif

    <div style="margin-top: 20px;">
        <details>
            <summary style="cursor: pointer; font-weight: bold;">Lösung anzeigen</summary>
            <pre style="background: #f4f4f4; padding: 10px; border: 1px solid #ddd; margin-top: 5px;">{{ $solution }}</pre>
        </details>
    </div>

</x-app-layout>
