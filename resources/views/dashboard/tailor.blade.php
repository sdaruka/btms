@extends('layouts.main')
@section('dashboard' , 'active')
@section('content')
    <div class="row">
        <div class="col-md-12 mb-3">
         <h2 class="fw-bold"> Welcome {{ Auth::user()->name }}</h2>
        </div>
        <div class="col-md-6 col-xl-3">
            <a href="{{route('orders.index')}}">
            <x-metric-card title="Active Orders" value="{{$pendingOrders}}" note="+{{$recentOrders->count()}} since last week" /></div>
                </a>
                {{-- <div class="col-md-6 col-xl-3"><x-metric-card title="Pending Fittings" value="3" note="Due this week" /></div> --}}
        {{-- <div class="col-md-6 col-xl-3"><x-metric-card title="New Clients" value="+5" note="This month" /></div> --}}
        {{-- <div class="col-md-6 col-xl-3"><x-metric-card title="Monthly Revenue" value="$2,350" note="+15% from last month" /></div> --}}
    </div>

    <div class="card mt-4">
        <div class="card-header fw-bold">Recent Orders</div>
        <div class="card-body text-muted">
            <p>A list of your most recent job orders will be displayed here.</p>
        </div>
    </div>
@endsection