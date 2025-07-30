@extends('layouts.main')
@section('title', 'Add Product')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">Create Product</h2>
            <a href="{{ route('products.index') }}" class="btn btn-warning">
                <i class="fa fa-backward-step me-1"></i> Back
            </a>
        </div>

        @if (Session::has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ Session::get('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data"
            class="card shadow-sm p-4">
            @csrf

            {{-- Product Name --}}
            <div class="mb-3">
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name') }}" placeholder="Product Name" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Description --}}
            <div class="mb-3">
                <textarea name="description" id="description" rows="3"
                    class="form-control @error('description') is-invalid @enderror" placeholder="Product Description">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Measurements --}}
            <div class="mb-3">
                <label class="form-label fw-semibold">Select Measurements</label>
                <div class="row">
                    @foreach ($measurements as $measurement)
                        <div class="col-6 col-md-3">
                            <div class="form-check">
                                <input type="checkbox" name="measurement_ids[]" value="{{ $measurement['id'] }}"
                                    class="form-check-input" id="measurement_{{ $measurement['id'] }}"
                                    {{ in_array($measurement['id'], old('measurement_ids', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="measurement_{{ $measurement['id'] }}">
                                    {{ $measurement['name'] }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('measurement_ids')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            {{-- Rate & Effective Date --}}
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="rate" class="form-label">Rate (INR)</label>
                    <input type="number" name="rate" id="rate" value="{{ old('rate') }}"
                        class="form-control @error('rate') is-invalid @enderror" placeholder="e.g. 500" required>
                    @error('rate')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="effective_date" class="form-label">Effective From</label>
                    <input type="date" name="effective_date" id="effective_date" value="{{ old('effective_date') }}"
                        class="form-control @error('effective_date') is-invalid @enderror" required>
                    @error('effective_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Product Images --}}
            <div class="mb-4">
                <label for="images" class="form-label fw-semibold">Upload Product Images</label>
                <input type="file" name="images[]" multiple class="form-control" accept="image/*">

                @if ($errors->has('images'))
                    <div class="text-danger">{{ $errors->first('images') }}</div>
                @endif

                @foreach ($errors->get('images.*') as $messages)
                    @foreach ($messages as $message)
                        <div class="text-danger">{{ $message }}</div>
                    @endforeach
                @endforeach
            </div>

            {{-- Submit Button --}}
            <div class="d-grid">
                <button type="submit" class="btn btn-danger btn-lg">
                    <i class="fa fa-save me-1"></i> Save Product
                </button>
            </div>
        </form>
    </div>
@endsection
