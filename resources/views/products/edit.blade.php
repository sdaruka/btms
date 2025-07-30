@extends('layouts.main')
@section('title','Edit Product')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Edit Product</h2>
        <a href="{{ route('products.index') }}" class="btn btn-warning">
            <i class="fa fa-arrow-left me-1"></i> Back
        </a>
    </div>

    <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data" class="card shadow-sm p-4">
        @csrf
        {{-- @method('PUT') --}}

        {{-- Product Details --}}
        <h5 class="fw-semibold mb-3 border-bottom pb-2">Product Details</h5>
        <div class="mb-3">
            <label class="form-label">Product Name <span class="text-danger">*</span></label>
            <input type="text" name="name" value="{{ old('name', $product->name) }}"
                   class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Summer Kurti">
            @error('name')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <div class="mb-4">
            <label class="form-label">Description</label>
            <textarea name="description" rows="3" class="form-control" placeholder="Short description...">{{ old('description', $product->description) }}</textarea>
        </div>

        {{-- Measurement Selection --}}
        <h5 class="fw-semibold mb-3 border-bottom pb-2">Measurements Required</h5>
        <div class="row mb-4">
            @foreach ($measurements as $measurement)
                <div class="form-check col-md-3 col-sm-4 col-6 mb-2">
                    <input class="form-check-input"
                           type="checkbox"
                           id="measure_{{ $measurement->id }}"
                           name="measurement_ids[]"
                           value="{{ $measurement->id }}"
                           {{ in_array($measurement->id, $product->measurements->pluck('id')->toArray()) ? 'checked' : '' }}>
                    <label class="form-check-label" for="measure_{{ $measurement->id }}">{{ $measurement->name }}</label>
                </div>
            @endforeach
        </div>

        {{-- Pricing Section --}}
        <h5 class="fw-semibold mb-3 border-bottom pb-2">Pricing</h5>
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <label class="form-label">New Rate (optional)</label>
                <input type="number" name="rate" class="form-control" placeholder="e.g. 750">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Effective Date</label>
                <input type="date" name="effective_date" class="form-control" value="{{ today()->format('Y-m-d') }}">
            </div>
        </div>

        {{-- Upload More Designs --}}
        <h5 class="fw-semibold mb-3 border-bottom pb-2">Upload More Designs</h5>
        <div class="mb-4">
            <input type="file" name="images[]" class="form-control" multiple accept="image/*">
            <small class="text-muted">You can upload multiple images</small>
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn btn-success w-100">
            <i class="fa fa-save me-1"></i> Update Product
        </button>
    </form>

    {{-- Existing Designs --}}
    @if ($product->designs->count())
        <hr class="my-5">
        <h5 class="fw-semibold mb-3">Existing Designs</h5>
        <div class="row g-3">
            @foreach ($product->designs as $design)
                <div class="col-6 col-md-3">
                    <div class="border rounded shadow-sm overflow-hidden h-100">
                        <img src="{{ asset( $design->design_image) }}"
                             class="img-fluid w-100" style="height: 160px; object-fit: cover;">
                        <div class="p-2 text-center small text-muted">
                            {{ $design->design_title }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
