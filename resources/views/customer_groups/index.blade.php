@extends('layouts.main')

@section('title', 'Customer Groups')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Customer Groups</h2>
        <a href="{{ route('customer_groups.create') }}" class="btn btn-primary">
            <i class="fa fa-plus me-2"></i> Add New Group
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($customerGroups->isEmpty())
        <div class="alert alert-info">No customer groups found.</div>
    @else
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Customers</th> {{-- Optional: Display count of associated customers --}}
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customerGroups as $group)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $group->name }}</td>
                            <td>{{ $group->users_count ?? $group->users->count() }}</td> {{-- Requires `withCount('users')` in controller or loading relationship --}}
                            <td>
                                <a href="{{ route('customer_groups.edit', $group) }}" class="btn btn-sm btn-info me-2" title="Edit">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <form action="{{ route('customer_groups.destroy', $group) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this customer group?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $customerGroups->links() }} {{-- Pagination links --}}
    @endif
</div>
@endsection