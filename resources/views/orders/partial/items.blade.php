{{-- Product Selector --}}
<div class="mb-4 row align-items-end g-2">
    <div class="col-12 col-md-8">
        <div class="input-group">
            <span class="input-group-text text-warning bg-dark">Select Product</span>
            <select x-model="selectedProduct" class="form-select border-warning shadow-sm">
                <option value="">-- Choose an item --</option>
                <template x-for="prod in products" :key="prod.id">
                    <option :value="prod.id" x-text="prod.name"></option>
                </template>
            </select>
            <span x-show="!isEdit && !customerJustChanged" class="input-group-text text-light bg-danger cursor-pointer"
                  @click="showItemModal = true">
                <i class="fa fa-circle-plus"></i>
            </span>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <button type="button" class="btn btn-warning w-100 fw-semibold mt-2 mt-md-0"
                :disabled="!selectedProduct" @click="addItem()">
            <i class="fa fa-circle-plus me-1"></i> Add Item
        </button>
    </div>
</div>

{{-- Items --}}
<div id="itemsContainer">
    <template x-for="(item, idx) in items" :key="item.id">
        <div class="card mb-4 border-warning" :data-product-id="item.id">
            <div class="card-header d-flex justify-content-between align-items-center bg-dark text-white">
                <strong x-text="item.name"></strong>
                <button type="button" class="btn-close btn-close-white" @click="removeItem(idx)"></button>
            </div>
            <div class="card-body px-2 px-md-4">
                <input type="hidden" :name="`items[${idx}][product_id]`" :value="item.id">

                {{-- Measurements --}}
                <div class="mb-3">
                    <label class="form-label">Measurements</label>
                    <div class="row">
                        <template x-for="(meas, mi) in item.measurements" :key="meas.id">
                            <div class="col-12 col-sm-6 col-md-3 mb-2">
                                <div class="input-group">
                                    <span class="input-group-text bg-dark text-light" x-text="meas.name"></span>
                                    <input type="hidden" :name="`items[${idx}][measurements][${mi}][id]`"
                                           :value="meas.id">
                                    <input type="text" class="form-control"
                                           :name="`items[${idx}][measurements][${mi}][value]`"
                                           :placeholder="`e.g. 34 for ${meas.name}`"
                                           x-model="meas.pivot ? meas.pivot.value : ''"> {{-- Updated x-model for existing values --}}
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Design Selection --}}
                <div class="mb-3">
                    <label class="form-label">Select Designs</label>
                    <div class="d-flex flex-nowrap overflow-auto gap-2 scroll-zone">
                        <template x-for="design in item.designs" :key="design.id">
                            <label class="thumb-wrapper position-relative border rounded p-2"
                                   style="cursor: pointer; min-width: 120px;"
                                   :class="{ 'border-danger border-2': item.selectedDesignIds.includes(design.id) }">
                                <input type="checkbox" :value="design.id" x-model="item.selectedDesignIds"
                                       :name="`items[${idx}][design_ids][]`"
                                       class="form-check-input position-absolute top-0 end-0 m-1">
                                <img :src="design.image_url" class="img-fluid mb-1 thumb" :alt="design.design_title" loading="lazy">
                                <img :src="design.image_url" class="thumb-preview" :alt="design.design_title"
                                     @mousemove="(e) => { $el.style.left = (e.pageX + 20) + 'px'; $el.style.top = (e.pageY + 20) + 'px'; }"
                                     style="position: absolute; display: none;"> {{-- Initial hide for preview --}}
                                <div class="text-center small mt-1" x-text="design.design_title"></div>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- Custom Design Upload --}}
                <div class="row">
                    <div class="col-12 col-md-8 mb-3">
                        <div class="input-group">
                            <input type="file" :name="`items[${idx}][custom_design_images][]`" {{-- Corrected name attribute --}}
                                   class="form-control border-warning"
                                   multiple
                                   accept="image/*"
                                   @change="handleCustomDesignUpload(idx, $event)">
                                 <span class="input-group-text text-warning bg-dark">Upload Design</span>

                        </div>
                        <template x-if="item.custom_design_images_names && item.custom_design_images_names.length > 0">
                            <div class="mt-2 small text-muted">
                                Selected file(s): <span x-text="item.custom_design_images_names.join(', ')"></span>
                            </div>
                        </template>
                        <template x-if="isEdit && item.id && item.existing_custom_design_urls && item.existing_custom_design_urls.length > 0">
                             <div class="mt-2 small text-info">
                                 Existing Custom Design(s):
                                 <template x-for="(imgUrl, imgIdx) in item.existing_custom_design_urls" :key="imgIdx">
                                     <a :href="imgUrl" target="_blank" class="text-info" x-text="imgUrl.split('/').pop()"></a><span x-show="imgIdx < item.existing_custom_design_urls.length - 1">, </span>
                                 </template>
                             </div>
                        </template>
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <div class="input-group">
                            <span class="input-group-text text-warning bg-dark">Design Name</span>
                            <input type="text" x-model="item.custom_design_title" {{-- Added x-model --}}
                                   :name="`items[${idx}][custom_design_title]`"
                                   class="form-control" placeholder="e.g. My Sketch">
                        </div>
                    </div>
                </div>

                {{-- Narration for Each Item (New Field) --}}
                <div class="mb-3">
                    <label :for="`item-narration-${idx}`" class="form-label">Item Narration/Notes</label>
                    <textarea
                        :id="`item-narration-${idx}`"
                        x-model="item.narration" {{-- Bind with x-model --}}
                        :name="`items[${idx}][narration]`"
                        class="form-control"
                        rows="2"
                        placeholder="Add special instructions or notes for this item (e.g., 'Extra stitching', 'Specific color code')"></textarea>
                </div>

                {{-- Rate & Quantity --}}
                <div class="row">
                    <div class="col-6 col-md-4 mb-2">
                        <div class="input-group">
                            <span class="input-group-text text-warning bg-dark">Std. Rate</span>
                            <input type="number" :name="`items[${idx}][rate]`" x-model="item.rate"
                                   class="form-control" step="0.01">
                        </div>
                    </div>
                    <div class="col-6 col-md-4 mb-2">
                        <div class="input-group">
                            <span class="input-group-text text-warning bg-dark">Quantity</span>
                            <input type="number" :name="`items[${idx}][quantity]`" x-model="item.quantity"
                                   class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

{{-- Responsive Enhancements --}}
<style>
    .thumb {
        height: 100px;
        object-fit: cover;
        border-radius: 6px;
        transition: transform 0.2s;
        width: 100%;
    }

    .thumb:hover {
        transform: scale(1.05);
    }

    .thumb-wrapper {
        position: relative;
    }

    .thumb-preview {
        display: none;
        position: relative;
        z-index: 9999;
        width: 250px;
        border-radius: 8px;
        box-shadow: 0 0 12px rgba(0, 0, 0, 0.4);
        border: 2px solid #ffc107;
        pointer-events: none;
    }

    .thumb-wrapper:hover .thumb-preview {
        display: block !important;
    }

    @media (max-width: 480px) {
        .thumb-preview {
            display: none !important;
        }
    }

    .scroll-zone {
        scroll-snap-type: x mandatory;
        scroll-padding-left: 12px;
    }

    .scroll-zone > * {
        scroll-snap-align: start;
    }
</style>