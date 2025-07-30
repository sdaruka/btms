@extends('layouts.main')

@section('title', 'Create Customer Group')

@section('content')
<div class="container mt-4">
    <h2>Create New Customer Group</h2>
    <hr>
    <form action="{{ route('customer_groups.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label">Group Name</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <a href="{{ route('customer_groups.index') }}" class="btn btn-secondary me-2">Cancel</a>
        <button type="submit" class="btn btn-primary">Create Group</button>
    </form>
</div>
@endsection