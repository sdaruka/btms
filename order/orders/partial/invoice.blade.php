{{-- Invoice Summary --}}
<div class="card shadow-lg mb-2 rounded bg-dark text-white">
    <div class="card-header">
        <h5 class="mb-0"><i class="fa-solid fa-file-invoice me-2"></i> Invoice Summary</h5>
    </div>

    <div class="card-body">
        <div class="row g-3 align-items-end text-warning">

            {{-- Notice --}}
            <div class="col-12">
                <p class="fw-semibold mb-1">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                    Review your order details before placing the order.
                </p>
            </div>

            {{-- Subtotal --}}
            <div class="col-md-3">
                <div class="d-flex justify-content-between align-items-center bg-dark rounded px-3 py-2 border border-secondary">
                    <span><i class="fa-solid fa-file-invoice-dollar me-2"></i> Sub Total</span>
                    <strong x-text="'₹' + subtotal.toFixed(2)"></strong>
                </div>
            </div>

            {{-- Design Charge --}}
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text bg-dark text-warning border-secondary">
                        <i class="fa-solid fa-pen-ruler me-1"></i> Design Charge
                    </span>
                    <input type="number"
                           name="design_charge"
                           id="design_charge"
                           x-model="design_charge"
                           class="form-control text-warning bg-dark border-secondary"
                           step="0.01"
                           placeholder="0.00">
                </div>
            </div>

            {{-- Discount --}}
            <div class="col-md-2">
                <div class="input-group">
                    <span class="input-group-text bg-dark text-warning border-secondary">
                        <i class="fa-solid fa-tag me-1"></i> Discount
                    </span>
                    <input type="number"
                           name="discount"
                           x-model="discount"
                           class="form-control text-warning bg-dark border-secondary"
                           min="0"
                           placeholder="0.00">
                </div>
            </div>

            {{-- Received --}}
            <div class="col-md-2">
                <div class="input-group">
                    <span class="input-group-text bg-dark text-warning border-secondary">
                        <i class="fa-solid fa-hand-holding-dollar me-1"></i> Received
                    </span>
                    <input type="number"
                           name="received"
                           x-model="received"
                           class="form-control text-warning bg-dark border-secondary"
                           step="0.01"
                           placeholder="0.00">
                </div>
            </div>

            {{-- Total --}}
            <div class="col-md-2">
                <div class="input-group">
                    <span class="input-group-text bg-dark text-warning border-secondary fw-bold">
                        <i class="fa-solid fa-coins me-1"></i> Total
                    </span>
                    <input type="text"
                           name="total_amount"
                           :value="total.toFixed(2)"
                           class="form-control text-warning bg-dark border-secondary fw-bold"
                           readonly>
                </div>
            </div>

            {{-- Remarks --}}
            <div class="col-12">
                <input type="text"
                       name="remarks"
                       x-model="remarks" {{-- Added x-model to remarks --}}
                       class="form-control mt-2 text-warning bg-dark border-secondary"
                       placeholder="Additional remarks...">
            </div>
        </div>
    </div>
</div>