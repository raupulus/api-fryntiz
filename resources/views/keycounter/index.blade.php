<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" integrity="sha384-1CmrxMRARb6aLqgBO7yyAxTOQE2AKb9GfXnEo760AUcUmFx3ibVJJAzGytlQcNXd" crossorigin="anonymous"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.3/dist/Chart.min.js"></script>

<div class="row">
    <div class="col-md-12 text-center">
        <h1>
            Estadísticas desde {{$keyboard_statistics['period_start']}}
            hasta {{$keyboard_statistics['period_end']}}
        </h1>
    </div>

    <div class="col-md-12 text-center">
        <h2>Cambiar periodo</h2>

        <form action="{{route('keycounter.index')}}" method="GET">
            <div class="row">
                <div class="col-md-6 text-left form-group">
                    Año:
                    <select name="year" class="form-control">
                        @foreach(range(2019, date('Y')) as $y)
                            <option value="{{$y}}" {{($year && ($year == $y)) ? 'selected' : ''}}>
                                Año {{$y}}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 text-left form-group">
                    Mes:
                    <select name="month" class="form-control">
                        @foreach(range(1, 12) as $m)
                            <option value="{{$m}}" {{($month && ($month == $m)) ? 'selected' : ''}}>
                                {{$meses[$m - 1]}}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-12 text-center">
                    <button type="submit" class="btn btn-secondary">
                        Filtrar
                    </button>
                </div>

            </div>
        </form>
    </div>


    <div class="col-md-10 mx-auto text-center">
        <canvas id="line-chart" width="600" height="450"
                style="max-height: 600px; max-width: 1000px; margin: auto"></canvas>
    </div>
</div>

<div class="row">

    <div class="col-md-12 text-center">
        <h1>Estadísticas por cada 100 resultados</h1>
        <p>
            Registro en hora UTC
        </p>
    </div>

    <div class="card-deck m-5">
        <div class="card text-white bg-info mb-3" style="max-width: 18rem;">
            <div class="card-header">Total de pulsaciones</div>
            <div class="card-body">
                <h5 class="card-title"></h5>
                <p class="card-text">
                    {{$keyboard->sum('pulsations')}}
                </p>
            </div>
        </div>

        <div class="card text-white bg-dark mb-3" style="max-width: 18rem;">
            <div class="card-header">Total de puntuación</div>
            <div class="card-body">
                <h5 class="card-title"></h5>
                <p class="card-text">
                    {{$keyboard->sum('score')}}
                </p>
            </div>
        </div>

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

    <div class="col-md-12 text-center">
        <h1>KEYBOARD</h1>
    </div>

    <div class="col-md-12 p-5">
        <table class="table table-sm table-striped table-dark table-bordered table-hover"
               style="width: 100%; overflow: scroll; display: block;">
            <thead>
            <tr>
                <td>nº</td>
                <td>start_at</td>
                <td>end_at</td>
                <td>duration</td>
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
                    <td>{{$reg->duration}}</td>
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
        <table class="table table-sm table-striped table-dark table-bordered table-hover"
               style="max-width: 100%; overflow: scroll; display: block;">
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

@php
$stats = $keyboard_statistics['data'];
$colors = ['#3e95cd', '#8e5ea2', '#3cba9f', '#e8c3b9', '#c45850', '#ff0000',
           '#00ff00', '#0000ff'];

$labels = [];
$dataset = [];
$datasetTMP = [];

foreach ($stats as $s) {
    $labels[] = (new Carbon\Carbon($s->day))->format('d');

    if (isset($datasetTMP[$s->device_id])) {
        $datasetTMP[$s->device_id]['data'][] = $s->total_pulsations;
    } else {
        $datasetTMP[$s->device_id] = [
            'data' => [$s->total_pulsations],
            'label' => $s->device_name,
            'borderColor' => $colors[$s->device_id],
            'fill' => 'false'
        ];
    }
}

foreach ($datasetTMP as $d) {
    $dataset[] = $d;
}


$labelsString = implode(',', array_unique($labels));
$datasetJson = json_encode($dataset);


@endphp

<script>
    var labels = "{{$labelsString}}".split(',');
    var datasetJsonString = '<?= $datasetJson ?>';
    var dataset = JSON.parse(datasetJsonString);

    new Chart(document.getElementById("line-chart"), {
        type: 'line',
        data: {
            labels: labels,
            datasets: dataset
        },
        options: {
            title: {
                display: true,
                text: 'Gráfica de pulsaciones diarias por dispositivos',
            },
            scales:{
                labelString: 'pppp',
                yAxes:[{
                    scaleLabel: {
                        display: true,
                        labelString: 'Pulsaciones Totales'
                    }
                }],
                xAxes:[{
                    scaleLabel: {
                        display: true,
                        labelString: 'Días en el mes: {{$month ? $meses[$month-1] : 'actual'}}'
                    }
                }],
            }
        }
    });
</script>
