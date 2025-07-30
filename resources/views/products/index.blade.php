@extends('layouts.main')
@section('title','Product')

@section('content')
    <div class="container">
        <div class="row mb-4 g-2 align-items-center">
            <div class="col-12 col-md-4">
                <h2 class="fw-bold mb-0">Manage Products</h2>
            </div>
            @if (Session::has('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ Session::get('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @elseif (Session::has('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ Session::get('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            <div class="col-12 col-md-6">
                <form action="{{ route('products.index') }}" method="GET" class="d-flex">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control me-2"
                        placeholder="Search by product name">
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-search"></i>
                    </button>
                </form>
            </div>
            <div class="col-12 col-md-2 text-md-end">
                <a href="{{ route('products.create') }}" class="btn btn-warning w-100 w-md-auto">
                    <i class="fa fa-shirt me-1"></i> Add Product
                </a>
            </div>
        </div>
        @if ($products->count())
            <div class="table-responsive-sm">
                <table class="table table-striped table-bordered align-middle shadow-sm w-100">
                    <thead>
                        <tr class="bg-dark text-white">
                            <th>#</th>
                            <th>Name</th>
                            <th class="text-center">Measurements Required</th>
                            <th>Rate</th>

                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $product->name }}</td>
                                <td class="text-center">
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach ($product->measurements as $measurement)
                                            <span class="badge bg-secondary">{{ $measurement->name }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    ₹{{ optional($product->rates->sortByDesc('effective_date')->first())->rate ?? '-' }}
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-light border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('products.show', $product) }}">
                                                    <i class="fa fa-eye me-2 text-info"></i> View
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('products.edit', $product) }}">
                                                    <i class="fa fa-edit me-2 text-warning"></i> Edit
                                                </a>
                                            </li>
                                            <li>
                                                <form action="{{ route('products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fa fa-trash me-2"></i> Delete
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $products->links('pagination::bootstrap-5') }}
            </div>
        @else
            <div class="alert alert-info">No products found.</div>
        @endif
    </div>
@endsection
