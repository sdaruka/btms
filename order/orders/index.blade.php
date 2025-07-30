@extends('layouts.main')

@section('title', 'Orders')

@section('content')
<div
  x-data="{
    page: {{ $orders->currentPage() }},
    lastPage: {{ $orders->lastPage() }},
    loading: false,
    hideDelivered: {{ request()->boolean('hide_delivered', true) ? 'true' : 'false' }},
    searchQuery: '{{ request('search') }}',
    searchDate: '{{ request('search_date') }}',

    buildQueryParams() {
      const params = new URLSearchParams();
      if (this.searchQuery) params.append('search', this.searchQuery);
      if (this.searchDate) params.append('search_date', this.searchDate);
      params.append('hide_delivered', this.hideDelivered ? 'true' : 'false');
      return params.toString();
    },

    refreshOrders() {
      this.page = 1;
      this.loading = true;
      const params = this.buildQueryParams();
      fetch(`{{ route('orders.index') }}?${params}`)
        .then(res => res.text())
        .then(html => {
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');
          const newOrdersGrid = doc.querySelector('.orders-grid');
          const currentGrid = document.querySelector('.orders-grid');

          if (!currentGrid || !newOrdersGrid) {
            console.warn('Missing .orders-grid during refresh');
            this.loading = false;
            return;
          }

          currentGrid.innerHTML = newOrdersGrid.innerHTML;

          // More reliable page update via data attributes
          const newPageEl = doc.querySelector('[data-page]');
          const lastPageEl = doc.querySelector('[data-last-page]');
          if (newPageEl && lastPageEl) {
            this.page = parseInt(newPageEl.dataset.page);
            this.lastPage = parseInt(lastPageEl.dataset.lastPage);
          }

          this.loading = false;
        })
        .catch(error => {
          console.error('Error refreshing orders:', error);
          this.loading = false;
        });
    },

    fetchNext() {
      if (this.page >= this.lastPage || this.loading) return;
      this.loading = true;
      const params = this.buildQueryParams();
      fetch(`{{ route('orders.index') }}?page=${this.page + 1}&${params}`)
        .then(res => res.text())
        .then(html => {
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');
          const newCards = doc.querySelectorAll('.card-order');
          const currentGrid = document.querySelector('.orders-grid');
          newCards.forEach(card => currentGrid.appendChild(card));
          this.page++;
          this.loading = false;
        })
        .catch(error => {
          console.error('Error fetching next page:', error);
          this.loading = false;
        });
    }
  }"
  x-init="() => {
    let touchStartY = 0;
    let touchEndY = 0;
    $el.addEventListener('touchstart', e => touchStartY = e.changedTouches[0].clientY);
    $el.addEventListener('touchend', e => {
      touchEndY = e.changedTouches[0].clientY;
      if (touchStartY - touchEndY > 50 && !this.loading && this.page < this.lastPage) {
        this.fetchNext();
      }
    });
  }"
>

    <div class="container">
        <div class="row mb-4 g-2 align-items-center ">
            <div class="col-12 col-md-4">
    <div class="d-flex justify-content-between align-items-center">
        <h2 class="fw-bold mb-0">Orders</h2>

        <div class="form-check form-switch me-md-3 mb-2 mb-md-0" style="cursor:pointer;">
            <input class="form-check-input" type="checkbox" id="hideDeliveredSwitch"
                   x-model="hideDelivered" @change="refreshOrders()">
            <label class="form-check-label text-warning" for="hideDeliveredSwitch">Hide Delivered</label>
        </div>
    </div>
</div>
            
            <div class="col-12 col-md-6">
                <form action="{{ route('orders.index') }}" method="GET" class="d-flex flex-column flex-md-row">

                    <input type="text" name="search" value="{{ request('search') }}"
                        class="form-control me-md-2 mb-0 mb-md-0" placeholder="Search by customer name or order number">

                    <input type="date" name="search_date" class="form-control me-md-2 mb-0 mb-md-0"
                        value="{{ request('search_date') }}" />

                    <button type="submit" class="btn btn-success">

                        <i class="fa fa-search"></i>

                    </button>

                </form>

            </div>

            <div class="col-12 col-md-2 text-md-end">

                @if (auth()->user()->role == 'admin' || auth()->user()->role == 'staff')
                    <a href="{{ route('orders.create') }}" class="btn btn-warning w-100 w-md-auto">

                        <i class="fa-solid fa-boxes-stacked me-2"></i>New Order</a>
                @endif

            </div>

        </div>


        <div class="row g-3 orders-grid">

        @if ($orders->count())

            <div class="row g-3">

                @foreach ($orders->sortBy('delivery_date') as $order)
                    
                    @php
                        \Carbon\Carbon::parse($order->delivery_date)->isPast() &&
                            $order->status !== 'Completed' &&
                            $order->status !== 'Delivered';

                        $isOverdue =
                            \Carbon\Carbon::parse($order->delivery_date)->isPast() &&
                            $order->status !== 'Completed' &&
                            $order->status !== 'Delivered';

                        $isPendingSoon =
                            \Carbon\Carbon::parse($order->delivery_date)->isBetween(now(), now()->copy()->addDays(3)) &&
                            $order->status !== 'Completed';

                        $statusClass =
                            [
                                'Assigned' => 'bg-primary',

                                'Completed' => 'bg-info',

                                'Pending' => 'bg-warning text-dark',

                                'Cancelled' => 'bg-danger',

                                'Alteration' => 'bg-dark',

                                'Delivered' => 'bg-success',

                                'In Progress' => 'bg-light text-info',
                            ][$order->status] ?? 'bg-secondary';

                    @endphp



                    <div class="col-12 col-md-6 col-lg-4 card-order">

                        <a href="{{ route('orders.show', $order) }}">



                            <div
                                class="card h-100 {{ $isOverdue ? 'border-danger shadow-sm' : ($isPendingSoon ? 'border-warning shadow-sm' : '') }}">

                                <div class="card-body d-flex flex-column">

                                    <h5 class="card-title d-flex justify-content-between align-items-center">

                                        <span>Order

                                            #{{ $order->order_number ?? $loop->iteration + ($orders->currentPage() - 1) * $orders->perPage() }}</span>

                                        <span x-data="{ status: '{{ $order->status }}' }">

                                            <span class="badge"
                                                :class="{
                                                
                                                    'bg-primary': status === 'Assigned',
                                                
                                                    'bg-info': status === 'Completed',
                                                
                                                    'bg-warning text-dark': status === 'Pending',
                                                
                                                    'bg-danger': status === 'Cancelled',
                                                
                                                    'bg-dark': status === 'Alteration',
                                                
                                                    'bg-success': status === 'Delivered',
                                                
                                                    'bg-light text-info': status === 'In Progress',
                                                
                                                    'bg-secondary': ![
                                                
                                                        'Assigned', 'Pending', 'Completed', 'Cancelled',
                                                
                                                        'Alteration', 'Delivered', 'In Progress'
                                                
                                                    ].includes(status)
                                                
                                                }"
                                                x-text="status"></span>



                                            @if ($order->status === 'Assigned' || ($order->status === 'Alteration' && $order->artisan))
                                                <p class="text-muted small d-block mt-1">

                                                    {{ $order->artisan?->name ??
                                                    $order->tailor?->name ??'Unknown User' }}

                                                </p>
                                            @endif

                                        </span>



                                    </h5>



                                    <h6 class="card-subtitle mb-2">

                                        Customer:

                                        {{ $order->user->name }}

                                        @if ($order->user->role != 'admin' && $order->user->role != 'staff')
                                            | {{ $order->user->phone }}
                                        @endif



                                    </h6>

                                    <p class="card-text mb-1">

                                        <strong>Delivery Date:</strong>

                                        {{ \Carbon\Carbon::parse($order->delivery_date)->format('d M Y') }}

                                        @if ($isOverdue)
                                            <span class="badge bg-danger ms-2">Overdue</span>
                                        @elseif ($isPendingSoon)
                                            <span class="badge bg-warning ms-2">Pending Soon</span>
                                        @endif

                                    </p>

                                    @if (auth()->user()->role == 'admin' || auth()->user()->role == 'staff')
                                        <p class="card-text mb-3"><strong>Total:</strong>

                                            ₹{{ number_format($order->total_amount, 2) }}</p>
                                    @endif

                        </a>

                        <div class="mt-auto d-flex justify-content-between align-items-center">



                            {{-- ALPINE.JS STATUS DROPDOWN --}}

                            <div class="dropdown" x-data="{ open: false, status: '{{ $order->status }}' }" @click.outside="open = false"
                                style="position: relative;"> {{-- Added position: relative --}}

                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                    @click="open = !open" aria-expanded="false">

                                    Update Status

                                </button>

                                <ul class="dropdown-menu" :class="{ 'show': open }"
                                    style="position: absolute; z-index: 1050; top: 100%; left: 0;">

                                    {{-- Enhanced positioning --}}

                                    @if (auth()->user()->role == 'admin' || auth()->user()->role == 'staff')
                                        @foreach (['Assigned', 'Pending', 'Completed', 'Cancelled', 'Alteration', 'Delivered', 'In Progress'] as $s)
                                            <li>

                                                <button type="button" class="dropdown-item"
                                                    @click="

                                                                fetch('{{ route('orders.updateStatus', $order) }}', {

                                                                    method: 'PATCH',

                                                                    headers: {

                                                                        'Content-Type': 'application/json',

                                                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'

                                                                    },

                                                                    body: JSON.stringify({ status: '{{ $s }}' })

                                                                }).then(res => res.ok && (status = '{{ $s }}'));

                                                                open = false; // Close dropdown after selection

                                                            ">

                                                    {{ $s }}

                                                </button>

                                            </li>
                                        @endforeach
                                    @else
                                        @foreach (['Assigned', 'Completed', 'In Progress'] as $s)
                                            <li>

                                                <button type="button" class="dropdown-item"
                                                    @click="

                                                                fetch('{{ route('orders.updateStatus', $order) }}', {

                                                                    method: 'PATCH',

                                                                    headers: {

                                                                        'Content-Type': 'application/json',

                                                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'

                                                                    },

                                                                    body: JSON.stringify({ status: '{{ $s }}' })

                                                                }).then(res => res.ok && (status = '{{ $s }}'));

                                                                open = false; // Close dropdown after selection

                                                            ">

                                                    {{ $s }}

                                                </button>

                                            </li>
                                        @endforeach
                                    @endif

                                </ul>

                            </div>



                            {{-- ALPINE.JS ACTIONS DROPDOWN --}}

                            <div class="dropdown" x-data="{ open: false }" @click.outside="open = false"
                                style="position: relative;"> {{-- Added position: relative --}}

                                <button class="btn btn-sm btn-outline-dark" type="button" @click="open = !open"
                                    aria-expanded="false">

                                    <i class="fa fa-ellipsis-vertical"></i> Actions

                                </button>

                                <ul class="dropdown-menu dropdown-menu-end" :class="{ 'show': open }"
                                    style="position: absolute; z-index: 1050; top: 100%; right: 0;">

                                    {{-- Enhanced positioning --}}

                                    <li><a class="dropdown-item text-primary" href="{{ route('orders.print', $order) }}">

                                            <i class="fa fa-print me-2"></i>Print</a>

                                    </li>

                                    <li><a class="dropdown-item text-info" href="{{ route('orders.show', $order) }}">

                                            <i class="fa fa-eye me-2"></i>View</a>

                                    </li>

                                    @if (auth()->user()->role == 'admin' || auth()->user()->role == 'staff')
                                        <li><a class="dropdown-item text-warning"
                                                href="{{ route('orders.edit', $order) }}">

                                                <i class="fa fa-edit me-2"></i>Edit</a>

                                        </li>

                                        <li>

                                            <form action="{{ route('orders.destroy', $order) }}" method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this order? This action cannot be undone.')">

                                                @csrf

                                                @method('DELETE')

                                                <button type="submit" class="dropdown-item text-danger">

                                                    <i class="fa fa-trash me-2"></i> Delete

                                                </button>

                                            </form>

                                        </li>
                                    @endif

                                    <li><a class="dropdown-item text-success" style="cursor: pointer" data-bs-toggle="modal"
                                            data-bs-target="#assignModal{{ $order->id }}">

                                            <i class="fa-solid fa-people-arrows me-2"></i> Assign To</a>

                                    </li>

                                </ul>

                            </div>

                        </div>

                    </div>

            </div>



    </div>

    @include('orders.assign', ['order' => $order, 'tailors' => $tailors])
    
    @endforeach
<div data-page="{{ $orders->currentPage() }}" data-last-page="{{ $orders->lastPage() }}" hidden></div>

    </div>
     <div x-show="loading" class="text-center mt-4">
  <i class="fa fa-spinner fa-spin"></i> Loading more...
</div>
    <div class="d-none d-sm-flex justify-content-center mt-4">
    {{ $orders->links('pagination::bootstrap-5') }}
</div>

@else
    <div class="alert alert-info text-center" role="alert">
        No orders found matching your criteria.

    </div>

    @endif
   
</div> {{-- End of alpine loader --}}

    </div>

@endsection
