<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" integrity="sha384-1CmrxMRARb6aLqgBO7yyAxTOQE2AKb9GfXnEo760AUcUmFx3ibVJJAzGytlQcNXd" crossorigin="anonymous"></script>


<div class="col-md-12 text-center">
<h1>KEYBOARD</h1>
</div>

<table class="table table-sm table-striped table-dark table-bordered table-hover">
    <thead>
    <tr>
        <td>start_at</td>
        <td>end_at</td>
        <td>pulsations</td>
        <td>pulsations_special_keys</td>
        <td>pulsation_average</td>
        <td>score</td>
        <td>weekday</td>
        <td>device_id</td>
        <td>device_name</td>
        <td>created_at</td>
    </tr>
    </thead>
    
    <tbody>
    @foreach($keyboard as $reg)
        <tr>
            <td>{{$reg->start_at}}</td>
            <td>{{$reg->end_at}}</td>
            <td>{{$reg->pulsations}}</td>
            <td>{{$reg->pulsations_special_keys}}</td>
            <td>{{$reg->pulsation_average}}</td>
            <td>{{$reg->score}}</td>
            <td>{{$reg->weekday}}</td>
            <td>{{$reg->device_id}}</td>
            <td>{{$reg->device_name}}</td>
            <td>{{$reg->created_at}}</td>
        </tr>
    @endforeach
    </tbody>
</table>


<div class="col-md-12 text-center">
    <h1>MOUSE</h1>
</div>

<table class="table table-sm table-striped table-dark table-bordered table-hover">
    <thead>
    <tr>
        <td>start_at</td>
        <td>end_at</td>
        <td>pulsations</td>
        <td>pulsations_special_keys</td>
        <td>pulsation_average</td>
        <td>score</td>
        <td>weekday</td>
        <td>device_id</td>
        <td>device_name</td>
        <td>created_at</td>
    </tr>
    </thead>

    <tbody>
    @foreach($mouse as $reg)
        <tr>
            <td>{{$reg->start_at}}</td>
            <td>{{$reg->end_at}}</td>
            <td>{{$reg->pulsations}}</td>
            <td>{{$reg->pulsations_special_keys}}</td>
            <td>{{$reg->pulsation_average}}</td>
            <td>{{$reg->score}}</td>
            <td>{{$reg->weekday}}</td>
            <td>{{$reg->device_id}}</td>
            <td>{{$reg->device_name}}</td>
            <td>{{$reg->created_at}}</td>
        </tr>
    @endforeach
    </tbody>
</table>


{!! $keyboard->links() !!}
