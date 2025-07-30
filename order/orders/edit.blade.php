@extends('layouts.main')
@section('title', 'Edit Order')
@section('content')

    <div class="container" x-data="orderForm(true)" x-init="initEdit({{ json_encode($order) }})"> {{-- Changed x-data to orderForm(true) and added initEdit --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">Edit Orders</h2>
            <a href="{{ route('orders.index') }}" class="btn btn-warning">
                <i class="fa fa-backward-step me-1"></i> Back
            </a>
        </div>

        <form action="{{ route('orders.update', $order) }}" method="POST" enctype="multipart/form-data">
            @csrf
            {{-- @method('PUT') UNCOMMENTED: Essential for Laravel PUT request --}}

            {{-- Validation Errors --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Basic Order Info --}}
            <div class="row mb-3 bg-dark text-white p-2 rounded">
                <div class="col-md-3">
                    {{-- Selected customer should be handled by x-model="selectedUserId" in the customer partial --}}
                    {{-- For now, keeping static select here, but better if customer partial is used for consistency --}}
                    <select name="user_id" class="form-select" x-model="selectedUserId">
                        @foreach ($customers as $user)
                            <option value="{{ $user->id }}" {{ $order->user_id == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} | {{ $user->phone }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-dark text-white">Order Date</span>
                        <input type="date" name="order_date" readonly
                            value="{{ old('order_date', \Carbon\Carbon::parse($order->order_date)->format('Y-m-d')) }}"
                            class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-dark text-white">Delivery Date</span>
                        <input type="date" name="delivery_date"
                            value="{{ old('delivery_date', \Carbon\Carbon::parse($order->delivery_date)->format('Y-m-d')) }}"
                            class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-dark text-white">Order No.</span>
                        <input type="text" name="order_number" value="{{ old('order_number', $order->order_number) }}"
                            class="form-control" readonly>
                    </div>
                </div>
            </div>

            {{-- Editable Items --}}
            @include('orders.partial.items')

            {{-- Invoice Summary --}}
            @include('orders.partial.invoice')

            <button type="submit" class="btn btn-danger mt-3 w-100">Update Order</button>
        </form>
    </div>

    {{-- Alpine Component --}}
    @include('orders.partial.alpine')

@endsection