@extends('layouts.main')

@section('title', 'Order Details')

@section('content')
    <div class="container">
        {{-- <pre>{{dd($order)}}</pre> --}}
        <div class="row d-flex justify-content-between">
            <div class="col-md-4">
                <h2 class="mb-4">Order #{{ $order->order_number }}</h2>
            </div>

            <div class="col-md-8 text-md-end">
                @if (auth()->user()->role == 'admin' || auth()->user()->role == 'staff')
                    <a href="{{ route('orders.edit', $order) }}" class="btn btn-warning fw-semibold"><i
                            class="fa fa-edit"></i>Edit Order</a>
                @endif
                <a href="{{ route('orders.index') }}" class="btn btn-info fw-semibold">
                    <i class="fa fa-arrow-left"></i> Back to Orders
                </a>
                <a href="{{ route('orders.print', $order) }}" class="btn btn-sm btn-success fw-semibold">
                    <i class="fas fa-print"></i> 
                </a>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <p><strong>Customer:</strong> {{ $order->user->name }}</p>
                <p><strong>Delivery Date:</strong> {{ \Carbon\Carbon::parse($order->delivery_date)->format('d M Y') }}</p>
                <p><strong>Order Date:</strong> {{ \Carbon\Carbon::parse($order->order_date)->format('d M Y') }}</p>
                @if (auth()->user()->role == 'admin' || auth()->user()->role == 'staff')
                    <p><strong>Design Charge:</strong> ₹{{ number_format($order->design_charge, 2) }}</p>
                    <p><strong>Discount:</strong> ₹{{ number_format($order->discount, 2) }}</p>
                    <p><strong>Received:</strong> ₹{{ number_format($order->received, 2) }}</p>

                    <p><strong>Total Amount:</strong> ₹{{ number_format($order->total_amount, 2) }}</p>
                @endif
                <p><strong>Remarks:</strong> {{ $order->remarks }}</p>
            </div>
        </div>

        @foreach ($order->items as $item)
            <div class="card mb-3">
                <div class="card-header">
                    <strong>{{ $item->product->name }}</strong> × {{ $item->quantity }}
                </div>
                <div class="card-body">
                    @if (auth()->user()->role == 'admin' || auth()->user()->role == 'staff')
                        <p><strong>Rate:</strong> ₹{{ number_format($item->rate, 2) }}</p>
                        <p><strong>Amount:</strong> ₹{{ number_format($item->rate * $item->quantity, 2) }}</p>
                    @endif
                    @if ($item->designs->count())
                        <div class="mb-2">
                            <strong>Selected Designs:</strong>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach ($item->designs as $design)
                                    <div class="border rounded p-2" style="width: 140px;">
                                        <img src="{{ asset($design->design_image) }}" class="img-fluid mb-1"
                                            style="height: 80px; object-fit: cover;">
                                        <small>{{ $design->design_title }}</small>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($item->custom_design_image)
                        <div class="mb-2">
                            <strong>Uploaded Custom Design:</strong><br>
                            <img src="{{ $item->design_image_url }}" class="img-fluid mb-2" style="max-height: 120px;">
                            <p><small>Title: {{ $item->custom_design_title ?? '—' }}</small></p>
                        </div>
                    @endif

                    <div>
                        <strong>Measurements:</strong>
                        <ul class="list-group list-group-flush">
                            @foreach ($item->measurements as $m)
                            @if($m->pivot->value)
                                <li class="list-group-item">{{ $m->name }}: {{ $m->pivot->value }}</li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
