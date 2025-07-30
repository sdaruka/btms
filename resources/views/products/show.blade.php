@extends('layouts.main')
@section('title','Product Details')

@section('content')
    <div class="container">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">View Product</h2>
            <a href="{{ route('products.index') }}" class="btn btn-warning">
                <i class="fa fa-backward-step me-1"></i> Back
            </a>
        </div>

        {{-- Alerts --}}
        @if (Session::has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ Session::get('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (Session::has('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ Session::get('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Product Info --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h4 class="card-title fw-bold">{{ $product->name }}</h4>
                <p class="card-text">{{ $product->description ?? 'No description provided.' }}</p>

                <hr>

                <h6 class="fw-semibold">Measurements Required:</h6>
                @if ($product->measurements->count())
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($product->measurements as $measurement)
                            <span class="badge bg-secondary">{{ $measurement->name }}</span>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted">None assigned</p>
                @endif

                <hr>

                <h6 class="fw-semibold">Rate History:</h6>
                @if ($product->rates->count())
                    <ul class="list-group list-group-flush">
                        @foreach ($product->rates->sortByDesc('effective_date') as $rate)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>₹{{ $rate->rate }}</span>
                                <small class="text-muted">Effective from
                                    {{ \Carbon\Carbon::parse($rate->effective_date)->format('d M Y') }}</small>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted">No rates defined</p>
                @endif
            </div>
        </div>

        {{-- Design Thumbnails --}}
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="fw-semibold">Default Designs:</h6>
                {{-- <pre>{{dd($product->designs)}}</pre> --}}
                @if ($product->designs->count())
                    <div class="row g-3">

                        @foreach ($product->designs as $design)
                            <div class="col-6 col-md-3">
                                <div class="border rounded overflow-hidden shadow-sm preview-hover">
                                    <img src="{{ asset( $design->design_image) }}"
                                        data-image="{{ asset($design->design_image) }}"
                                        alt="{{ $design->design_title }}" class="img-fluid design-thumbnail"
                                        style="height: 150px; object-fit: cover; cursor: zoom-in;">

                                    <div class="p-2 text-center small text-muted">
                                        {{ $design->design_title }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted">No designs uploaded</p>
                @endif
            </div>
        </div>
    </div>
    {{-- Modal HTML --}}
<div class="modal fade" id="designModal" tabindex="-1" aria-labelledby="designModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content bg-dark">
        <div class="modal-body p-0">
          <img src="" id="fullImage" class="w-100" style="max-height: 80vh; object-fit: contain;">
        </div>
      </div>
    </div>
  </div>
  
  {{-- JS --}}
  <script>
      document.addEventListener('DOMContentLoaded', function () {
          const thumbnails = document.querySelectorAll('.design-thumbnail');
          const fullImage = document.getElementById('fullImage');
          thumbnails.forEach(thumb => {
              thumb.addEventListener('click', () => {
                  fullImage.src = thumb.dataset.image;
                  new bootstrap.Modal(document.getElementById('designModal')).show();
              });
          });
      });
    //   console.log('Modal script loaded');
  </script>
@endsection
