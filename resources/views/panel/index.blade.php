@extends('panel.layouts.app')

@section('title', 'Panel Admin - Dashboard')

@section('content')
    @include('panel.layouts.breadcrumbs')
    @include('panel.layouts.top-cards')

    <!-- Area Chart Example-->
    <div class="card mb-3">
        <div class="card-header">
            <i class="fas fa-chart-area"></i>
            Area Chart Example</div>
        <div class="card-body">
            <canvas id="myAreaChart" width="100%" height="30"></canvas>
        </div>
        <div class="card-footer small text-muted">Updated yesterday at 11:59 PM</div>
    </div>

    <!-- DataTables Example -->
    @include('panel.demos._static_table')
@endsection
