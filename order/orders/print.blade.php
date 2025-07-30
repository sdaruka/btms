@extends('layouts.main')

@section('content')
<style>
    @media print {
        @page {
            size: A5 portrait;
            margin: 0;
        }
        
        body {
            background: none !important;
             margin: 0 !important; /* Also remove body margin */
        padding: 0 !important;
        background: none !important;
        }
        .print-container {
            page-break-inside: avoid;
            box-shadow: none;
            border: none !important;
            margin: 0 !important;
        }
        .table th, .table td {
            border: 1px solid #000 !important;
        }
        .btn, .navbar, .dropdown-menu, .modal, .sidebar {
            display: none !important;
        }
        img {
            max-width: 200px !important;
        }
    }

    .table th, .table td {
        vertical-align: middle !important;
    }

    .invoice-header h5 {
        font-size: 1.2rem;
    }

    .logo-img {
        max-width: 60px;
    }

    .print-container {
        font-size: 13px;
        max-width: 850px;
        background: white;
    }
</style>

<script>
    window.onload = function () {
        window.print();
    };
</script>

@php
    $subtotal = $order->items->sum(fn($i) => $i->rate * $i->quantity);
@endphp

@foreach (['Customer' => 'Customer Copy', 'Tailor' => 'Tailor Copy'] as $type => $title)
<div class="print-container container my-3 p-3 border">
    <div class="row align-items-center mb-2 invoice-header">
        <div class="col-3 text-start">
            <img src="{{ asset('/images/logo.png') }}" alt="Bhumis" class="logo-img" style="width:10vw">
        </div>
        <div class="col-6 text-center">
            <h5 class="fw-bold mb-0">{{ $title }}</h5>
        </div>
        <div class="col-3"></div>
    </div>

    <div class="row mb-2">
        <div class="col-4"><strong>Name:</strong> {{ $order->user->name }}</div>
        <div class="col-4"><strong>Phone:</strong> {{ $order->user->phone }}</div>
        <div class="col-4 text-end"><strong>Order No.:</strong> #{{ $order->order_number }}</div>
    </div>

    <div class="row mb-2">
        <div class="col-6"><strong>Order Date:</strong> {{ $order->order_date }}</div>
        <div class="col-6 text-end"><strong>Delivery Date:</strong> {{ $order->delivery_date }}</div>
    </div>

    @if ($type === 'Customer')
        <table class="table table-sm table-bordered mt-3 text-center">
            <thead class="table-light">
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Rate</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product->name ?? '-' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->rate, 2) }}</td>
                    <td>{{ number_format($item->rate * $item->quantity, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="row text-end mt-2">
            <div class="col-12">
                <strong>Subtotal:</strong> ₹{{ number_format($subtotal, 2) }}<br>
                <strong>Discount:</strong> ₹{{ number_format($order->discount, 2) }}<br>
                <strong>Received:</strong> ₹{{ number_format($order->received, 2) }}<br>
                <strong>Total:</strong> ₹{{ number_format($order->total_amount, 2) }}
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-end">
            <strong>Company Seal & Signature:</strong> ______________________
        </div>
    @else
        <table class="table table-sm table-bordered mt-3 text-center">
            <thead class="table-light">
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Measurements</th>
                    <th>Design</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product->name ?? '-' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td class="text-start">
                        @foreach($item->measurements as $m)
                        @if($m->pivot->value)
                            <div><strong>{{ $m->name }}:</strong> {{ $m->pivot->value }}</div>
                            @endif
                        @endforeach
                    </td>
                    <td>
                        @foreach($item->designs as $design)
                            <img src="{{ asset('storage/' . $design->design_image) }}"
                                 alt="{{ $design->design_title ?? 'Design' }}"
                                 class="img-thumbnail mb-1"
                                 style="max-height: 60px;">
                        @endforeach
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4 d-flex justify-content-end">
            <strong>Tailor Signature:</strong> ______________________
        </div>
    @endif

    <small class="text-muted d-block mt-3 text-end">Printed on {{ now()->format('d M Y H:i') }}</small>
</div>

@if (!$loop->last)
<div style="page-break-after: always;"></div>
@endif
@endforeach
@endsection
