<div id="{{$id}}" class="r-table-container">
    <table class="r-table">
        @if($hasHeader)
            <thead>
            <tr>
                @foreach($columns as $column)
                    <th>{{$column}}</th>
                @endforeach
            </tr>
            </thead>
        @endif
        <tbody>

        @foreach($rows as $row)
            <tr>
                @foreach($row as $idx => $field)
                    @if($hasHeader)
                        @php($columnTitle = $columns[$idx] ?? '')
                        <td><span class="r-table-field-head">{{$columnTitle ? $columnTitle . ': ' : ''}}</span>{{$field}}</td>
                    @else
                        <td>{{$field}}</td>
                    @endif
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

{{--
<div id="{{$id}}" class="r-table-container">
    <table class="r-table">
        @if($hasHeader)
            <thead>
            <tr>
                @foreach($columns as $column)
                    <th>{{$column}}</th>
                @endforeach
            </tr>
            </thead>
        @endif

        <tbody>
        @foreach($rows as $row)
            <tr>
                @foreach($row as $field)
                    <td>{{$field}}</td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
--}}
