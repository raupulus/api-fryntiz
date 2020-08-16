<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" integrity="sha384-1CmrxMRARb6aLqgBO7yyAxTOQE2AKb9GfXnEo760AUcUmFx3ibVJJAzGytlQcNXd" crossorigin="anonymous"></script>

<div class="row">
    <div class="col-md-12 text-center">
        <h1>
            Registros del plantas
        </h1>

        <p>
            Condiciones para encender motor de riego:
        </p>

        <ul>
            <li>Tanque de agua lleno</li>
            <li>Humedad de tierra al 35%</li>
        </ul>

        <p>
            Condiciones para vaporizador de agua:
        </p>

        <ul>
            <li>Temperatura ambiente menor a 30ºC</li>
            <li>Humedad ambiente menor a 60%</li>
        </ul>
    </div>
</div>

<div class="row">

    <div class="col-md-12 text-center">
        <h1>Estadísticas por cada 100 resultados</h1>
        <p>
            Registro en hora UTC
        </p>
    </div>

    @foreach($smartplants as $plant)
        <div class="col-md-12 text-center">
            <h2>{{$plant->name}}</h2>
            <h3>(<small>Scientific Name:</small> {{$plant->name_scientific}})</h3>

            <br />

            <div class="smartplant-description">
                {!! $plant->description !!}
            </div>

            <br />

            {{-- Añado imagen si existe --}}
            @if($plant->image)
            <div class="row">
                <div class="col-md-12">
                    <img src="{{$plant->urlImage}}"
                         title="{{$plant->name}}"
                         alt="{{$plant->name}}"
                         style="width: 300px; margin: auto;"/>
                </div>
            </div>
            @endif

            <br />

            {{-- Detalles de la planta --}}
            <div class="row">
                <div class="col-md-10 offset-md-1 text-left mb-3 p-2">
                    <div class="smartplant-details">
                        {!! $plant->details !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12 p-5">
            <table class="table table-sm table-striped table-dark table-bordered table-hover"
                   style="width: 90%; margin:auto; overflow: scroll; display: block;">
                <thead>
                <tr>
                    <th>UV</th>
                    <th>Temperatura</th>
                    <th>Humedad ambiente</th>
                    <th>Humedad en tierra</th>
                    <th>Tanque de agua lleno</th>
                    <th>Motor de riego activo</th>
                    <th>Vaporizador de agua activo</th>
                    <th>Momento del registro</th>
                </tr>
                </thead>

                <tbody>

                @foreach($plant->last100registers() as $reg)
                    <tr>
                        <td>{{$reg->uv ?? 0}}</td>
                        <td>{{$reg->temperature ?? 0.00}}</td>
                        <td>{{$reg->humidity ?? 0.00}}</td>
                        <td>{{$reg->soil_humidity}}</td>
                        <td>{{$reg->full_water_tank ? 'Si' : 'No'}}</td>
                        <td>{{$reg->waterpump_enabled ? 'Si' : 'No'}}</td>
                        <td>{{$reg->vaporizer_enabled ? 'Si' : 'No'}}</td>
                        <td>{{$reg->created_at}}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</div>


<style>
    .smartplant-description {
        font-size: 0.8em;
    }

    .smartplant-details {
        font-size: 0.6em;
    }
</style>
