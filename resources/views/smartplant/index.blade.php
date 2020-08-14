<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" integrity="sha384-1CmrxMRARb6aLqgBO7yyAxTOQE2AKb9GfXnEo760AUcUmFx3ibVJJAzGytlQcNXd" crossorigin="anonymous"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.3/dist/Chart.min.js"></script>

<div class="row">
    <div class="col-md-12 text-center">
        <h1>
            Regstros del plantas
        </h1>
    </div>
</div>

<div class="row">

    <div class="col-md-12 text-center">
        <h1>Estadísticas por cada 100 resultados</h1>
        <p>
            Registro en hora UTC
        </p>
    </div>

    @foreach($smartPlants as $plant)
        <div class="col-md-12 text-center">
            <h2>{{$plant->name}}</h2>
            <h3>{{$plant->name_scientific}}</h3>

            <br />

            <p>
                {{$plant->description}}
            </p>
        </div>

        <div class="col-md-12 p-5">
            <table class="table table-sm table-striped table-dark table-bordered table-hover"
                   style="width: 100%; overflow: scroll; display: block;">
                <thead>
                <tr>
                    <td>UV</td>
                    <td>Temperatura</td>
                    <td>Humedad ambiente</td>
                    <td>Humedad en tierra</td>
                    <td>Tanque de agua lleno</td>
                    <td>Motor de riego activo</td>
                    <td>Vaporizador de agua activo</td>
                    <td>Momento del registro</td>
                </tr>
                </thead>

                <tbody>
                @foreach($plant->registers as $reg)
                    <tr>
                        <td>{{$reg->uv}}</td>
                        <td>{{$reg->temperature}}</td>
                        <td>{{$reg->humidity}}</td>
                        <td>{{$reg->soil_humidity}}</td>
                        <td>{{$reg->full_water_tank}}</td>
                        <td>{{$reg->waterpump_enabled}}</td>
                        <td>{{$reg->vaporizer_enabled}}</td>
                        <td>{{$reg->created_at}}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</div>
