@extends('layouts.main')
@section('title', 'New Order')
@section('content')

<div x-data="orderForm(false)" x-init="init()">
    <form
        action="{{ route('orders.store') }}"
        method="POST"
        enctype="multipart/form-data"
        x-ref="orderForm"
        @submit.prevent="submitForm"
    >
        @csrf

        <div class="container">
            <h2 class="mb-4">Place New Order</h2>

            {{-- Error Messages --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $msg)
                            <li>{{ $msg }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if (session('status'))
                <div class="alert alert-info">{{ session('status') }}</div>
            @endif

            {{-- Partials --}}
            @include('orders.partial.customer')
            @include('orders.partial.items')
            @include('products.product-modal')
            @include('orders.partial.invoice')

            <button type="submit" class="btn btn-danger w-100 mt-4">
                <i class="fa fa-save me-2"></i> Place Order
            </button>
        </div>
    </form>

    {{-- Customer Modal --}}
<div x-show="showModal" x-transition.opacity
     @keydown.escape.window="showModal = false; resetNewCustomerForm();"
     class="modal-backdrop"
     style="position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index: 1050; display: none;">

    <div class="modal-content"
         @click.away="showModal = false; resetNewCustomerForm();"
         style="background: white; padding: 20px; border-radius: 8px; margin: 10% auto; width: 90%; max-width: 400px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">

        <h5 class="mb-3">Add New Customer</h5>

        {{-- General Error Message (e.g., network error, non-validation API error) --}}
        <template x-if="customerErrorMessage">
            <div class="alert alert-danger mb-3" x-text="customerErrorMessage"></div>
        </template>

        {{-- Name Field --}}
        <div class="mb-3">
            <label for="newCustomerName" class="form-label">Customer Name</label>
            <input type="text" id="newCustomerName" x-model="newCustomer.name"
                   class="form-control"
                   :class="{'is-invalid': customerErrors.name}"
                   placeholder="Customer Name">
            <template x-if="customerErrors.name">
                <div class="invalid-feedback" x-text="customerErrors.name[0]"></div>
            </template>
        </div>

        {{-- Phone Field --}}
        <div class="mb-3">
            <label for="newCustomerPhone" class="form-label">Phone</label>
            <input type="text" id="newCustomerPhone" x-model="newCustomer.phone"
                   class="form-control"
                   :class="{'is-invalid': customerErrors.phone}"
                   placeholder="Phone (e.g., 1234567890)">
            <template x-if="customerErrors.phone">
                <div class="invalid-feedback" x-text="customerErrors.phone[0]"></div>
            </template>
        </div>

        <div class="d-flex justify-content-end mt-4">
            <button type="button" class="btn btn-secondary me-2" @click="showModal = false; resetNewCustomerForm();">Cancel</button>
            <button type="button" class="btn btn-primary" @click="createCustomer()" :disabled="isLoading">
                <span x-show="!isLoading">Save</span>
                <span x-show="isLoading">Saving...</span>
            </button>
        </div>
    </div>
</div>

    {{-- Image Preview --}}
    {{-- This element seems to be a remnant, typically Alpine handles hover preview within the thumb-wrapper --}}
    {{-- <img x-show="hoveredPreview" :src="hoveredPreview" class="thumb-preview" @mousemove="..." @mouseleave="hoveredPreview = null"> --}}

    {{-- Alpine Init --}}
    @include('orders.partial.alpine')
</div>
@endsection