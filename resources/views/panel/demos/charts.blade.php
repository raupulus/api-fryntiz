@extends('panel.layouts.app')

@section('title', 'Panel Admin - Dashboard')

@section('content')
    @include('panel.layouts.breadcrumbs')
    <div class="card mb-3">
        <div class="card-header">
            <i class="fas fa-chart-area"></i>
            Area Chart Example</div>
        <div class="card-body">
            <canvas id="myAreaChart" width="100%" height="30"></canvas>
        </div>
        <div class="card-footer small text-muted">Updated yesterday at 11:59 PM</div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">
                    <i class="fas fa-chart-bar"></i>
                    Bar Chart Example</div>
                <div class="card-body">
                    <canvas id="myBarChart" width="100%" height="50"></canvas>
                </div>
                <div class="card-footer small text-muted">Updated yesterday at 11:59 PM</div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <i class="fas fa-chart-pie"></i>
                    Pie Chart Example</div>
                <div class="card-body">
                    <canvas id="myPieChart" width="100%" height="100"></canvas>
                </div>
                <div class="card-footer small text-muted">Updated yesterday at 11:59 PM</div>
            </div>
        </div>
    </div>

    <p class="small text-center text-muted my-5">
        <em>More chart examples coming soon...</em>
    </p>
@endsection

@section('javascript')
    <script src="{{ url('admin-panel/js/demos/chart-area-demo.js') }}"></script>
    <script src="{{ url('admin-panel/js/demos/chart-bar-demo.js') }}"></script>
    <script src="{{ url('admin-panel/js/demos/chart-pie-demo.js') }}"></script>
@endsection
