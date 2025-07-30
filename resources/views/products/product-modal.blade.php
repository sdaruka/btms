@section('title', 'Add Product')

{{-- Modal Backdrop --}}
<div x-show="showItemModal" class="modal-backdrop"
     @keydown.escape.window="showItemModal = false"
     style="position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.6); z-index:1050; display: flex; justify-content: center; align-items: center;">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Modal Content --}}
    <div class="modal-content bg-white rounded-4 shadow-lg p-4 position-relative"
         style="max-width: 90vw; max-height: 90vh; width: 100%; margin: 0 auto; animation: fadeInUp 0.3s ease-out; overflow-y: auto;">
         {{--
            Key changes for mobile:
            - max-width: 90vw; (increased for mobile)
            - max-height: 90vh; (increased for mobile)
            - width: 100%; (ensures it takes available width within max-width)
            - margin: 0 auto; (centers it horizontally)
            - display: flex; justify-content: center; align-items: center; on modal-backdrop
              to properly center the modal vertically and horizontally.
            - overflow-y: auto; to allow scrolling if content overflows vertically.
         --}}

        <h5 class="mb-3 fw-semibold border-bottom pb-2">
            <i class="fa fa-box text-primary me-2"></i> Add Product
        </h5>

        <!-- Error Message -->
        <template x-if="productErrorMessage">
            <div class="alert alert-danger py-2 px-3 small mb-3" x-text="productErrorMessage"></div>
        </template>

        <!-- Product Name & Description - Stacked on Mobile -->
        <div class="mb-2"> {{-- Combined these into one div for better grouping --}}
            <div class="input-group mb-2"> {{-- Added mb-2 for spacing between name and description --}}
                <span class="input-group-text bg-dark text-warning">Product Name</span>
                <input type="text" x-model="newProduct.name" id="product-name" class="form-control" placeholder="e.g. Summer Kurti">
            </div>
            <div class="input-group">
                <span class="input-group-text bg-dark text-warning">Description</span>
                {{-- Changed input type to textarea for multi-line description --}}
                <textarea x-model="newProduct.description" id="product-description" class="form-control" rows="2" placeholder="Short description..."></textarea>
            </div>
        </div>

        <!-- Measurements -->
        <div class="mb-2">
            <label class="form-label d-block">Measurements</label>
            <div class="row">
                <template x-for="(m, index) in measurements" :key="m.id">
                    {{-- Adjusted column for better mobile layout. col-6 for 2 items per row on small screens. --}}
                    <div class="col-6 col-md-2 mb-1"> 
                        <div class="form-check">
                            <input type="checkbox" :id="`m-${m.id}`" :value="m.id" x-model="newProduct.measurement_ids" class="form-check-input">
                            <label class="form-check-label small" :for="`m-${m.id}`" x-text="m.name"></label>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Rate & Date -->
        <div class="row g-2 mb-2">
            {{-- Made both columns full width on small screens (col-12) --}}
            <div class="col-12"> 
                <div class="input-group">
                    <span class="input-group-text bg-dark text-warning">Rate (₹)</span>
                    <input type="number" x-model="newProduct.rate" id="product-rate" class="form-control" placeholder="e.g. 500">
                </div>
            </div>
            <div class="col-12"> {{-- Made both columns full width on small screens (col-12) --}}
                <div class="input-group">
                    <span class="input-group-text bg-dark text-warning">Date</span>
                    <input type="date" x-model="newProduct.effective_date" id="effective-date" class="form-control">
                </div>
            </div>
        </div>

        <!-- Images -->
        <div class="mb-2">
            <label class="form-label" for="product-images">Upload Images</label>
            <input type="file" id="product-images" @change="newProduct.images = $event.target.files" multiple accept="image/*" class="form-control">
            <small class="text-muted">You can select multiple files</small>
        </div>

        <!-- Actions -->
        <div class="d-flex justify-content-end gap-2 mt-3">
            <button type="button" class="btn btn-outline-secondary" @click="showItemModal = false">
                <i class="fa fa-times me-1"></i> Cancel
            </button>
            <button type="button" class="btn btn-success" @click="createProduct()">
                <i class="fa fa-check me-1"></i> Save
            </button>
        </div>
    </div>
</div>

<style>
/* Basic animation for modal entry */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Optional: Add a media query for very small screens if needed, 
   though Bootstrap's col- classes handle most responsiveness. */
@media (max-width: 576px) { /* Bootstrap's 'sm' breakpoint */
    .modal-content {
        padding: 1rem; /* Reduce padding on very small screens */
    }
}
</style>