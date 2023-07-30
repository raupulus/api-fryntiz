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

        @foreach($rows as $row)
            <tr>
                @foreach($columns as $column)
                    <td>{{$column}}</td>
                @endforeach
            </tr>
        @endforeach
    </table>
</div>
