@extends('layouts.main')

@section('content')
<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold">Notifications</h4>
        @if($notifications->count() > 0)
            <form action="{{ route('notifications.read_all') }}" method="POST">
                @csrf
                <button class="btn btn-outline-primary btn-sm">Mark All as Read</button>
            </form>
        @endif
    </div>

    <!-- Add an ID here for real-time updates -->
    <div id="notification-list">
        @forelse($notifications as $notification)
            <div class="card mb-3 shadow-sm border @if(!$notification->is_read) border-warning @endif">
                <div class="card-body d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="fw-bold mb-1">{{ $notification->title }}</h6>
                        <p class="mb-2 text-muted">{{ $notification->message }}</p>
                        @if($notification->order_id)
                            <a href="{{ route('orders.show', $notification->order_id) }}" class="btn btn-sm btn-outline-warning">
                                View
                            </a>
                        @endif
                    </div>

                    <div class="text-end">
                        <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small><br>
                        @if(!$notification->is_read)
                            <a href="{{ route('notifications.read', $notification->id) }}" class="btn btn-sm btn-success mt-1">
                                Mark as Read
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-info">
                No notifications yet.
            </div>
        @endforelse
    </div>


    <div class="d-flex justify-content-center mt-4">
        {{ $notifications->links('pagination::bootstrap-5') }}
    </div>

</div>
@endsection
