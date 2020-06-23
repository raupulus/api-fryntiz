<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" integrity="sha384-1CmrxMRARb6aLqgBO7yyAxTOQE2AKb9GfXnEo760AUcUmFx3ibVJJAzGytlQcNXd" crossorigin="anonymous"></script>

<div class="row">

    <div class="col-md-12 text-center">
        <h1>KEYBOARD</h1>
    </div>

    <div class="card-deck m-5">
        <div class="card text-white bg-primary mb-3" style="max-width: 18rem;">
            <div class="card-header">Pulsaciones media</div>
            <div class="card-body">
                <h5 class="card-title"></h5>
                <p class="card-text">
                    {{$keyboard->avg('pulsations')}}
                </p>
            </div>
        </div>

        <div class="card text-white bg-success mb-3" style="max-width: 18rem;">
            <div class="card-header">Pulsaciones media para teclas
                especiales</div>
            <div class="card-body">
                <h5 class="card-title"></h5>
                <p class="card-text">
                    {{$keyboard->avg('pulsations_special_keys')}}
                </p>
            </div>
        </div>
        <div class="card text-white bg-danger mb-3" style="max-width: 18rem;">
            <div class="card-header">Pulsaciones media por minuto</div>
            <div class="card-body">
                <h5 class="card-title"></h5>
                <p class="card-text">
                    {{$keyboard->avg('pulsation_average')}}
                </p>
            </div>
        </div>
        <div class="card text-white bg-warning mb-3" style="max-width: 18rem;">
            <div class="card-header">Puntuación Media</div>
            <div class="card-body">
                <h5 class="card-title"></h5>
                <p class="card-text">
                    {{$keyboard->avg('score')}}
                </p>
            </div>
        </div>
    </div>

    <div class="col-md-12 p-5">
        <table class="table table-sm table-striped table-dark table-bordered table-hover">
            <thead>
            <tr>
                <td>nº</td>
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
                    <td>{{$reg->id}}</td>
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
    </div>

    <div class="col-md-12 text-center">
        <h1>MOUSE</h1>
    </div>

    <div class="col-md-12 p-5">
        <table class="table table-sm table-striped table-dark table-bordered table-hover">
            <thead>
            <tr>
                <td>nº</td>
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
                    <td>{{$reg->id}}</td>
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
    </div>

    <div class="col-md-12 text-center">
        {!! $keyboard->links() !!}
    </div>
</div>
