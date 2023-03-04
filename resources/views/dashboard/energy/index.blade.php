@extends('adminlte::page')

@section('title', 'Energía')

@section('content_header')
    <h1>
        <i class="fas fa-file-pdf"></i>
        Energía
    </h1>
@stop

@section('content')

    <div class="row" id="app">

        <div class="col-12">

            @foreach($hardwareMonitor as $monitor)

                <div class="box-card-energy row">
                    <div class="col-12">
                        <div class="text-center">
                            <img class="card-device-img"
                                 src="{{$monitor->urlImage}}"
                                 alt="{{$monitor->name}}">
                        </div>

                        <div class="text-center">
                            <span class="card-device-title">
                                {{$monitor->id}} - {{$monitor->name}}
                            </span>
                        </div>

                        <div>
                            @if($monitor->hardwareEnergyLoad->count())
                                <div class="device-energy-table card">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            Consumo de energía
                                        </h3>
                                    </div>

                                    <div class="card-body p-0">
                                        <table class="table table-striped">
                                            <thead>
                                            <tr>
                                                <th style="width: 10px">#</th>
                                                <th>Nombre</th>
                                                <th>Consumo</th>
                                                <th style="width: 40px">W</th>
                                            </tr>
                                            </thead>
                                            <tbody>


                                            @foreach ($monitor->hardwareEnergyLoad as $load)
                                                <tr>
                                                    <td>{{$load->id}}</td>
                                                    <td>{{$load->name}}</td>
                                                    <td>
                                                        <div class="progress progress-xs">
                                                            <div class="progress-bar progress-bar-danger" style="width: 55%"></div>
                                                        </div>
                                                    </td>
                                                    <td><span class="badge bg-danger">31</span></td>
                                                </tr>
                                            @endforeach

                                        </table>
                                    </div>

                                </div>
                            @endif

                            @if($monitor->hardwareEnergyGenerator->count())
                                    <div class="device-energy-table card">
                                        <div class="card-header">
                                            <h3 class="card-title">
                                                Generador de energía
                                            </h3>
                                        </div>

                                        <div class="card-body p-0">
                                            <table class="table table-striped">
                                                <thead>
                                                <tr>
                                                    <th style="width: 10px">#</th>
                                                    <th>Nombre</th>
                                                    <th>Consumo</th>
                                                    <th style="width: 40px">W</th>
                                                </tr>
                                                </thead>
                                                <tbody>


                                                @foreach ($monitor->hardwareEnergyGenerator as $generator)
                                                    <tr>
                                                        <td>{{$generator->id}}</td>
                                                        <td>{{$generator->name}}</td>
                                                        <td>
                                                            <div class="progress progress-xs">
                                                                <div class="progress-bar progress-bar-danger" style="width: 55%"></div>
                                                            </div>
                                                        </td>
                                                        <td><span class="badge bg-danger">31</span></td>
                                                    </tr>
                                                @endforeach

                                            </table>
                                        </div>

                                    </div>
                            @endif

                        </div>
                    </div>


                </div>
            @endforeach


        </div>
    </div>

@stop


@section('css')
    <style>
        .box-card-energy {
            background-color: #85937a;
            border: 2px solid #586c5c;
            border-radius: 5px;
            padding: 10px;
            margin: 10px 0;
        }

        .box-card-energy .card-device-img {
            width: 200px;
        }

        .box-card-energy .card-device-title {
            font-size: 1.5em;
            font-weight: bold;
        }

        .device-energy-table {
            background-color: #202e32;
            color: #dfdcb9;

        }

        @media (max-width: 768px) {
            .box-card-energy {
                display: block;
            }
        }

    </style>
@endsection
