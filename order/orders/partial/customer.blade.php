{{-- Customer & Delivery --}}
            <div class="row mb-4 bg-dark text-white p-2 rounded">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Customer</label>
                    <div class="input-group">
                        <select x-model="selectedUserId" @change="fetchPreviousMeasurements()" name="user_id"
                            class="form-select">
                            <option value="">Select customer</option>
                            <template x-for="cust in customers" :key="cust.id">
                                <option :value="String(cust.id)" x-text="`${cust.name} (${cust.phone})`"></option>
                            </template>
                        </select>

                        <span class="input-group-text bg-danger text-white cursor-pointer" @click="showModal = true">
                            <i class="fas fa-user-plus"></i>
                        </span>


                    </div>
                    @error('user_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Order Date</label>
                    <input type="date" name="order_date" class="form-control @error('Order_date') is-invalid @enderror"
                        value="{{ old('order_date', now()->format('Y-m-d')) }}" readonly>
                    @error('order_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Delivery Date</label>
                    <input type="date" name="delivery_date"
                        class="form-control @error('delivery_date') is-invalid @enderror"
                        value="{{ old('delivery_date', now()->addDays(10)->format('Y-m-d')) }}">
                    @error('delivery_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>


                <div class="col-md-3 mb-3">
                    <label class="form-label">Order Number</label>
                    <input type="number" name="order_number"
                        class="form-control @error('order_number') is-invalid @enderror" value="{{ $invoiceNumber }}"
                        readonly>
                    @error('order_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>