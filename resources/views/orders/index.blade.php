@extends('layouts.main')

@section('title', 'Orders')

@section('content')

    <div class="container-fluid">
        <div x-data="ordersList()" x-init="init()">
            {{-- Search and Filter Form --}}
            <form @submit.prevent="refreshOrders()" class="mb-4 p-3 bg-light rounded shadow-sm">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-2 mb-md-0">
                            <h2 class="fw-bold mb-0">Orders</h2>
                            <div class="form-check form-switch me-md-3" style="cursor:pointer;">
                                <input class="form-check-input" type="checkbox" id="hideDeliveredSwitch"
                                    x-model="hideDelivered" @change="refreshOrders()">
                                <label class="form-check-label text-warning" for="hideDeliveredSwitch">Hide
                                    Delivered</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="d-flex flex-column flex-md-row">
                            <input type="text" x-model="searchQuery" name="search"
                                class="form-control me-md-2 mb-2 mb-md-0"
                                placeholder="Search by customer name or order number">
                            <input type="date" x-model="searchDate" name="search_date"
                                class="form-control me-md-2 mb-2 mb-md-0" />
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-12 col-md-2 text-md-end">
                        @if (auth()->user()->role == 'admin' || auth()->user()->role == 'staff')
                            <a href="{{ route('orders.create') }}" class="btn btn-warning w-100 w-md-auto">
                                <i class="fa-solid fa-boxes-stacked me-2"></i>New Order
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            {{-- Loading and No Orders Found messages --}}
            <template x-if="loading && orders.length === 0">
                <div class="text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3">Loading orders...</p>
                </div>
            </template>

            <template x-if="!loading && orders.length === 0">
                <div class="alert alert-info text-center" role="alert">
                    No orders found matching your criteria.
                </div>
            </template>

            {{-- Orders List --}}
            {{-- Orders Grid (Managed by Alpine's x-for) --}}
            <div class="row g-3 orders-grid">
                <template x-for="order in orders" :key="order.id">
                    <div class="col-12 col-md-6 col-lg-4 card-order">
                        <a :href="`{{ url('/orders') }}/${order.id}`">
                            <div class="card h-100"
                                :class="{
                                    'border-danger shadow-sm': isOverdue(order),
                                    'border-warning shadow-sm': isPendingSoon(order)
                                }">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title d-flex justify-content-between align-items-center">
                                        <span>Order #<span x-text="order.order_number"></span></span>
                                        <span>
                                            <span class="badge"
                                                :class="{
                                                    'bg-primary': order.status === 'Assigned',
                                                    'bg-info': order.status === 'Completed',
                                                    'bg-warning text-dark': order.status === 'Pending',
                                                    'bg-danger': order.status === 'Cancelled',
                                                    'bg-dark': order.status === 'Alteration',
                                                    'bg-success': order.status === 'Delivered',
                                                    'bg-light text-info': order.status === 'In Progress',
                                                    'bg-secondary': !['Assigned', 'Pending', 'Completed', 'Cancelled',
                                                        'Alteration', 'Delivered', 'In Progress'
                                                    ].includes(order.status)
                                                }"
                                                x-text="order.status">
                                            </span>
                                            <template
                                                x-if="order.status === 'Assigned' || (order.status === 'Alteration' && order.artisan)">
                                                <p class="text-muted small d-block mt-1"
                                                    x-text="order.artisan?.name || 'Unknown Tailor'"></p>
                                            </template>
                                        </span>
                                    </h5>
                                    <h6 class="card-subtitle mb-2">
                                        Customer: <span x-text="order.user.name"></span>
                                        <template x-if="order.user.role != 'admin' && order.user.role != 'staff'">
                                            | <span x-text="order.user.phone"></span>
                                        </template>
                                    </h6>
                                    <p class="card-text mb-1">
                                        <strong>Delivery Date:</strong>
                                        <span x-text="formatDate(order.delivery_date)"></span>
                                        <template x-if="isOverdue(order)">
                                            <span class="badge bg-danger ms-2">Overdue</span>
                                        </template>
                                        <template x-if="isPendingSoon(order)">
                                            <span class="badge bg-warning ms-2">Pending Soon</span>
                                        </template>
                                    </p>
                                    @if (auth()->user()->role == 'admin' || auth()->user()->role == 'staff')
                                        <p class="card-text mb-3">
                                            <strong>Total:</strong> ₹<span
                                                x-text="formatCurrency(order.total_amount)"></span>
                                        </p>
                                    @endif
                        </a>

                        <div class="mt-auto d-flex justify-content-between align-items-center">
                            {{-- ALPINE.JS STATUS DROPDOWN --}}
                            <div class="dropdown" x-data="{ open: false, orderStatus: order.status }" @click.outside="open = false"
                                style="position: relative;">
                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                    @click="open = !open" aria-expanded="false">
                                    Update Status
                                </button>
                                <ul class="dropdown-menu" :class="{ 'show': open }"
                                    style="position: absolute; z-index: 1050; top: 100%; left: 0;">
                                    @if (auth()->user()->role == 'admin' || auth()->user()->role == 'staff')
                                        @foreach (['Assigned', 'Pending', 'Completed', 'Cancelled', 'Alteration', 'Delivered', 'In Progress'] as $s)
                                            <li>
                                                <button type="button" class="dropdown-item"
                                                    @click="updateOrderStatus(order.id, '{{ $s }}'); orderStatus = '{{ $s }}'; open = false;">
                                                    {{ $s }}
                                                </button>
                                            </li>
                                        @endforeach
                                    @else
                                        @foreach (['Assigned', 'Completed', 'In Progress'] as $s)
                                            <li>
                                                <button type="button" class="dropdown-item"
                                                    @click="updateOrderStatus(order.id, '{{ $s }}'); orderStatus = '{{ $s }}'; open = false;">
                                                    {{ $s }}
                                                </button>
                                            </li>
                                        @endforeach
                                    @endif
                                </ul>
                            </div>

                            {{-- ALPINE.JS ACTIONS DROPDOWN --}}
                            <div class="dropdown" x-data="{ open: false }" @click.outside="open = false"
                                style="position: relative;">
                                <button class="btn btn-sm btn-outline-dark" type="button" @click="open = !open"
                                    aria-expanded="false">
                                    <i class="fa fa-ellipsis-vertical"></i> Actions
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" :class="{ 'show': open }"
                                    style="position: absolute; z-index: 1050; top: 100%; right: 0;">
                                    <li><a class="dropdown-item text-primary"
                                            :href="`{{ url('/orders') }}/${order.id}/print`">
                                            <i class="fa fa-print me-2"></i>Print</a>
                                    </li>
                                    <li><a class="dropdown-item text-info" :href="`{{ url('/orders') }}/${order.id}`">
                                            <i class="fa fa-eye me-2"></i>View</a>
                                    </li>
                                    @if (auth()->user()->role == 'admin' || auth()->user()->role == 'staff')
                                        <li><a class="dropdown-item text-warning"
                                                :href="`{{ url('/orders') }}/${order.id}/edit`">
                                                <i class="fa fa-edit me-2"></i>Edit</a>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item text-danger"
                                                @click="deleteOrder(order.id)">
                                                <i class="fa fa-trash me-2"></i> Delete
                                            </button>
                                        </li>
                                    @endif
                                    <li>
                                        {{-- Modified Assign To button to use Alpine.js for modal control --}}
                                        <button type="button" class="dropdown-item text-success"
                                            @click.prevent="openAssignModal(order)">
                                            <i class="fa-solid fa-people-arrows me-2"></i> Assign To
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    </a>
            </div>
            </template>
        </div>

        {{-- Load More button for mobile --}}
        <template x-if="hasMorePages && !isDesktop()">
            <div class="text-center mt-4">
                <button @click="loadMoreOrders()" :disabled="loading" class="btn btn-primary">
                    <span x-show="loading" class="spinner-border spinner-border-sm me-2" role="status"
                        aria-hidden="true"></span>
                    <span x-text="loading ? 'Loading...' : 'Load More'"></span>
                </button>
            </div>
        </template>

        {{-- Desktop Pagination Links --}}
        <template x-if="isDesktop()">
            <div class="d-flex justify-content-center mt-4">
                {!! $orders->links('pagination::bootstrap-5') !!}
            </div>
        </template>

        {{-- SINGLE GLOBAL ASSIGN MODAL (Moved outside x-for) --}}
        <div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true"
            x-ref="assignModalElement">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="assignModalLabel">Assign Order <span
                                x-text="selectedOrder?.order_number ? '#' + selectedOrder.order_number : ''"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    {{-- The form's x-data now correctly uses selectedOrder?.artisan_id or selectedOrder?.tailor_id --}}
                    <form x-data="{ assignedTailorId: $store.ordersData.selectedOrder?.artisan_id || $store.ordersData.selectedOrder?.tailor_id }" @submit.prevent="assignOrderToTailor()">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="tailorSelect" class="form-label">Select Tailor</label>
                                <select class="form-select" id="tailorSelect" x-model="assignedTailorId" required>
                                    <option value="">-- Select Tailor --</option>
                                    {{-- Use $store.ordersData.tailors --}}
                                    <template x-for="tailor in $store.ordersData.tailors" :key="tailor.id">
                                        <option :value="tailor.id" x-text="tailor.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Assign</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>

    <script>
        // Create an Alpine Store for shared data like selectedOrder and tailors
        document.addEventListener('alpine:init', () => {
            Alpine.store('ordersData', {
                orders: @json($orders->items()),
                nextPageUrl: @json($orders->nextPageUrl()),
                hasMorePages: @json($orders->hasMorePages()),
                loading: false,
                searchQuery: '{{ request('search') }}',
                searchDate: '{{ request('search_date') }}',
                hideDelivered: {{ request()->boolean('hide_delivered', true) ? 'true' : 'false' }},
                selectedOrder: null, // Holds the order currently selected for assignment
                tailors: @json($tailors), // Pass tailors data from Blade
            });
        });

        function ordersList() {
            return {
                init() {
                    // Initialize orders from the Alpine store
                    this.orders = Alpine.store('ordersData').orders;
                    this.nextPageUrl = Alpine.store('ordersData').nextPageUrl;
                    this.hasMorePages = Alpine.store('ordersData').hasMorePages;
                    this.loading = Alpine.store('ordersData').loading;
                    this.searchQuery = Alpine.store('ordersData').searchQuery;
                    this.searchDate = Alpine.store('ordersData').searchDate;
                    this.hideDelivered = Alpine.store('ordersData').hideDelivered;

                    if (!this.isDesktop()) {
                        window.addEventListener('scroll', this.debounce(() => {
                            const {
                                scrollTop,
                                scrollHeight,
                                clientHeight
                            } = document.documentElement;
                            const nearBottom = scrollTop + clientHeight >= scrollHeight - 300;
                            if (nearBottom && !this.loading && this.hasMorePages) {
                                this.loadMoreOrders();
                            }
                        }, 200));
                    }
                    this.updateUrl();
                },

                // Proxy properties to the store
                get orders() {
                    return Alpine.store('ordersData').orders;
                },
                set orders(value) {
                    Alpine.store('ordersData').orders = value;
                },
                get nextPageUrl() {
                    return Alpine.store('ordersData').nextPageUrl;
                },
                set nextPageUrl(value) {
                    Alpine.store('ordersData').nextPageUrl = value;
                },
                get hasMorePages() {
                    return Alpine.store('ordersData').hasMorePages;
                },
                set hasMorePages(value) {
                    Alpine.store('ordersData').hasMorePages = value;
                },
                get loading() {
                    return Alpine.store('ordersData').loading;
                },
                set loading(value) {
                    Alpine.store('ordersData').loading = value;
                },
                get searchQuery() {
                    return Alpine.store('ordersData').searchQuery;
                },
                set searchQuery(value) {
                    Alpine.store('ordersData').searchQuery = value;
                },
                get searchDate() {
                    return Alpine.store('ordersData').searchDate;
                },
                set searchDate(value) {
                    Alpine.store('ordersData').searchDate = value;
                },
                get hideDelivered() {
                    return Alpine.store('ordersData').hideDelivered;
                },
                set hideDelivered(value) {
                    Alpine.store('ordersData').hideDelivered = value;
                },
                get selectedOrder() {
                    return Alpine.store('ordersData').selectedOrder;
                },
                set selectedOrder(value) {
                    Alpine.store('ordersData').selectedOrder = value;
                },
                get tailors() {
                    return Alpine.store('ordersData').tailors;
                }, // Just a getter, as tailors are static

                isDesktop() {
                    return window.innerWidth >= 768;
                },

                debounce(func, delay) {
                    let timeout;
                    return function(...args) {
                        const context = this;
                        clearTimeout(timeout);
                        timeout = setTimeout(() => func.apply(context, args), delay);
                    };
                },

                buildQueryParams(page = 1) {
                    const params = new URLSearchParams();
                    if (this.searchQuery) params.append('search', this.searchQuery);
                    if (this.searchDate) params.append('search_date', this.searchDate);
                    params.append('hide_delivered', this.hideDelivered ? 'true' : 'false');
                    params.append('page', page);
                    return params.toString();
                },

                updateUrl(page = 1) {
                    const url = new URL(window.location.href);
                    const params = this.buildQueryParams(page);
                    url.search = params;
                    history.replaceState(null, '', url.toString());
                },

                async refreshOrders() {
                    this.loading = true;
                    this.orders = [];
                    this.nextPageUrl = null;
                    this.hasMorePages = false;

                    const params = this.buildQueryParams(1);
                    const url = `{{ route('orders.index') }}?${params}`;

                    try {
                        const response = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                        const data = await response.json();
                        this.orders = data.data;
                        this.nextPageUrl = data.next_page_url;
                        this.hasMorePages = data.has_more_pages;
                        this.updateUrl(data.current_page);
                    } catch (error) {
                        console.error("Error refreshing orders:", error);
                    } finally {
                        this.loading = false;
                    }
                },

                async loadMoreOrders() {
                    if (!this.hasMorePages || this.loading || !this.nextPageUrl) {
                        return;
                    }
                    this.loading = true;
                    const urlObj = new URL(this.nextPageUrl);
                    const nextPage = urlObj.searchParams.get('page');
                    const params = this.buildQueryParams(nextPage);

                    try {
                        const response = await fetch(`${urlObj.origin}${urlObj.pathname}?${params}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                        const data = await response.json();
                        this.orders = [...this.orders, ...data.data];
                        this.nextPageUrl = data.next_page_url;
                        this.hasMorePages = data.has_more_pages;
                        this.updateUrl(data.current_page);
                    } catch (error) {
                        console.error("Error loading more orders:", error);
                    } finally {
                        this.loading = false;
                    }
                },

                formatDate(dateString) {
                    if (!dateString) return 'N/A';
                    const options = {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric'
                    };
                    return new Date(dateString).toLocaleDateString(undefined, options);
                },

                formatCurrency(amount) {
                    if (typeof amount === 'undefined' || amount === null) return '0.00';
                    return parseFloat(amount).toLocaleString('en-IN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                },

                isOverdue(order) {
                    const deliveryDate = new Date(order.delivery_date);
                    const now = new Date();
                    return deliveryDate < now && order.status !== 'Completed' && order.status !== 'Delivered';
                },

                isPendingSoon(order) {
                    const deliveryDate = new Date(order.delivery_date);
                    const now = new Date();
                    const threeDaysFromNow = new Date();
                    threeDaysFromNow.setDate(now.getDate() + 3);
                    return deliveryDate > now && deliveryDate <= threeDaysFromNow && order.status !== 'Completed';
                },

                async updateOrderStatus(orderId, newStatus) {
                    try {
                        const response = await fetch(`{{ url('/orders') }}/${orderId}/update-status`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                status: newStatus
                            })
                        });

                        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

                        this.orders = this.orders.map(order =>
                            order.id === orderId ? {
                                ...order,
                                status: newStatus
                            } : order
                        );
                    } catch (error) {
                        console.error('Error updating order status:', error);
                        alert('Failed to update status.');
                    }
                },

                async deleteOrder(orderId) {
                    if (!confirm('Are you sure you want to delete this order? This action cannot be undone.')) {
                        return;
                    }
                    try {
                        const response = await fetch(`{{ url('/orders') }}/${orderId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });
                        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                        this.orders = this.orders.filter(order => order.id !== orderId);
                        alert('Order deleted successfully!');
                    } catch (error) {
                        console.error('Error deleting order:', error);
                        alert('Failed to delete order.');
                    }
                },

                // New: Function to open the assign modal
                openAssignModal(order) {
                    this.selectedOrder = order; // Set the selected order data in the store
                    // Access the modal element using x-ref and manually show it
                    const assignModalElement = this.$refs.assignModalElement;
                    if (assignModalElement) {
                        const modal = new bootstrap.Modal(assignModalElement);
                        modal.show();
                    } else {
                        console.error('Assign modal element not found!');
                    }
                },

                // New: Function to handle assigning order to tailor
                async assignOrderToTailor() {
                    if (!this.selectedOrder) return;

                    const assignedTailorId = this.$el.querySelector('#tailorSelect').value;

                    if (!assignedTailorId) {
                        alert('Please select a tailor.');
                        return;
                    }

                    try {
                        // Corrected URL generation using Laravel's route helper
                        const assignUrl = `{{ route('orders.assign', ['order' => ':orderId']) }}`.replace(':orderId',
                            this.selectedOrder.id);

                        const response = await fetch(assignUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                tailor_id: assignedTailorId
                            })
                        });

                        if (!response.ok) {
                            const errorData = await response.json();
                            throw new Error(
                                `HTTP error! status: ${response.status}, message: ${errorData.message || 'Unknown error'}`
                                );
                        }

                        const updatedOrder = await response.json();
                        this.orders = this.orders.map(order =>
                            order.id === updatedOrder.id ? {
                                ...order,
                                ...updatedOrder
                            } : order
                        );

                        const modal = bootstrap.Modal.getInstance(this.$refs.assignModalElement);
                        if (modal) modal.hide();

                        alert('Order assigned successfully!');
                    } catch (error) {
                        console.error('Error assigning order:', error);
                        alert('Failed to assign order: ' + error.message);
                    }
                }
            };
        }
    </script>

    <style>
        @media (max-width: 767.98px) {
            .pagination {
                display: none !important;
            }
        }
    </style>
@endsection
