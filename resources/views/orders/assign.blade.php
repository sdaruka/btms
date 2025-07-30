<!-- Trigger Button -->
{{-- <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignModal{{ $order->id }}">
    Assign to Tailor
</button> --}}

<!-- Modal -->
<div class="modal fade" id="assignModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <form method="POST" action="{{ route('orders.assign', $order->id) }}">
            @csrf

            <input type="hidden" name="order_id" value="{{ $order->id }}">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Order #{{ $order->order_number }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <label for="assignedto" class="form-label">Select Tailor</label>
                    <select name="assignedto" id="assignedto" class="form-select">
                        @foreach($tailors as $tailor)
                            <option value="{{ $tailor->id }}">{{ $tailor->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="sumbit" class="btn btn-sm btn-danger w-100">Assign</button>
                </div>
            </div>
        </form>
    </div>
</div>