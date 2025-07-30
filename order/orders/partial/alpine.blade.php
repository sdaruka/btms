<script>
    function orderForm(isEdit = false, rawData = {}) {

        const products = @json($productsJson);

        const oldItems = @json(old('items') ?? ($order->items ?? []));
        const customers = window.initialCustomers = Array.from(
            new Map(@json($customers).map(c => [c.id, c])).values()
        );

        // Retrieve customerGroups passed from the controller
        const customerGroups = @json($customerGroups ?? []); // <-- NEW LINE: Pass customer groups from PHP

        return {
            customerJustChanged: false,
            isEdit,
            selectedUserId: @json(old('user_id') ?? ($order->user_id ?? '')),
            selectedCustomerId: @json(old('customer_id') ?? ($order->customer_id ?? '')),
            measurements: @json($measurements->map(fn($m) => ['id' => $m->id, 'name' => $m->name])),
            selectedProduct: '',
            products,
            customers,
            customerGroups, // <-- NEW LINE: Add to Alpine data
            discount: {{ old('discount', $order->discount ?? 0) }},
            design_charge: {{ old('design_charge', $order->design_charge ?? 0) }},
            received: {{ old('received', $order->received ?? 0) }},
            showModal: false, // For customer creation modal
            newCustomer: {
                name: '',
                phone: '',
                customer_group_id: customerGroups.length > 0 ? customerGroups[0].id : '' // <-- MODIFIED: Initialize with default group
            },
            customerErrors: {},
            customerErrorMessage: '',
            isLoading: false, // NEW: For the customer creation modal's save button

            previousMeasurements: {},
            items: [],
            hoveredPreview: null,

            productErrorMessage: '',
            showItemModal: false, // For product creation modal

            newProduct: {
                name: '',
                description: '',
                rate: '',
                effective_date: '',
                measurement_ids: [],
                images: [],
            },

            // NEW HELPER: Get CSRF token reliably
            getCsrfToken() {
                const tokenElement = document.head.querySelector('meta[name="csrf-token"]');
                return tokenElement ? tokenElement.content : '';
            },

            // NEW HELPER: Reset new customer form
            resetNewCustomerForm() {
                this.newCustomer = {
                    name: '',
                    phone: '',
                    customer_group_id: this.customerGroups.length > 0 ? this.customerGroups[0].id : '' // <-- MODIFIED: Reset with default group
                };
                this.customerErrors = {};
                this.customerErrorMessage = '';
                this.isLoading = false;
            },


            init() {
                this.items = oldItems.map(item => {
                    const prod = products.find(p => p.id == (item.product_id ?? item.id));
                    const designs = (prod?.designs || []).map(d => ({
                        id: d.id,
                        design_title: d.design_title,
                        image_url: d.image_url ?? '/placeholder.jpg'
                    }));

                    return {
                        id: prod?.id ?? item.product_id,
                        name: prod?.name ?? '',
                        rate: item.rate ?? prod?.current_rate ?? 0,
                        quantity: item.quantity || 1,
                        designs: designs,
                        selectedDesignIds: item.design_ids || item.designs?.map(d => d.id) || [],
                        custom_design_title: item.custom_design_title || '',
                        measurements: (prod?.measurements || []).map(m => {
                            const matched = (item.measurements || []).find(im => im.id === m.id);
                            return {
                                id: m.id,
                                name: m.name,
                                pivot: {
                                    value: matched?.pivot?.value || matched?.value || 0
                                }
                            };
                        })
                    };
                });

                // This $watch needs to be *inside* the init() method to be reactive
                this.$watch('selectedUserId', () => {
                    // Only clear if the customer actually changed and is not empty
                    if (this.customerJustChanged && this.selectedUserId !== '') {
                        for (let i = this.items.length - 1; i >= 0; i--) {
                            this.removeItem(i);
                        }
                        this.selectedProduct = '';
                        this.showItemModal = false;
                        this.discount = 0;
                        this.design_charge = 0;
                        this.received = 0;
                        this.hoveredPreview = null;
                        this.customerJustChanged = false; // Reset the flag
                    } else if (this.selectedUserId === '') {
                        // Clear all when customer is deselected
                         for (let i = this.items.length - 1; i >= 0; i--) {
                            this.removeItem(i);
                        }
                        this.selectedProduct = '';
                        this.showItemModal = false;
                        this.discount = 0;
                        this.design_charge = 0;
                        this.received = 0;
                        this.hoveredPreview = null;
                    }
                });

                // If on edit page, ensure correct customer is selected and measurements are fetched
                if (this.isEdit && this.selectedUserId) {
                    this.fetchPreviousMeasurements();
                }
            },

            addItem() {
                if (!this.selectedUserId) {
                    alert("Select a customer first.");
                    return;
                }

                const existing = this.items.find(i => i.id == this.selectedProduct);
                if (existing) {
                    alert("Item already added.");
                    this.selectedProduct = '';
                    return;
                }

                const prod = this.products.find(p => p.id == this.selectedProduct);
                if (!prod) return;

                const prev = this.previousMeasurements[this.selectedProduct]?.[0];

                const allowedIds = prod.measurements.map(m => m.id);

                const measurements = this.measurements
                    .filter(m => allowedIds.includes(m.id))
                    .map(m => {
                        const source = (prev?.measurements ?? prod.measurements).find(pm => pm.id === m.id);
                        return {
                            id: m.id,
                            name: m.name,
                            pivot: {
                                value: source?.pivot?.value ?? 0
                            }
                        };
                    });

                const designs = (prod?.designs || []).map(d => ({
                    id: d.id,
                    design_title: d.design_title,
                    image_url: d.image_url ?? '/placeholder.jpg'
                }));

                this.items.push({
                    id: String(prod.id),
                    name: prod.name,
                    rate: prod.current_rate ?? 0,
                    quantity: 1,
                    measurements,
                    designs,
                    selectedDesignIds: [],
                    custom_design_title: '',
                });

                this.selectedProduct = '';
            },

            removeItem(index) {
                this.items.splice(index, 1);
            },

            replaceProduct(i, productId) {
                const prod = this.products.find(p => p.id == productId);
                this.items[i].id = productId;
                this.items[i].rate = prod?.current_rate ?? 0;
                this.items[i].designs = (prod?.designs || []).map(d => ({
                    id: d.id,
                    design_title: d.design_title,
                    image_url: d.image_url ?? '/placeholder.jpg'
                }));
                this.items[i].measurements = (prod?.measurements || []).map(m => ({
                    id: m.id,
                    name: m.name,
                    pivot: {
                        value: ''
                    }
                }));
                this.items[i].selectedDesignIds = [];
            },

            fetchPreviousMeasurements() {
                fetch(`{{ url('/orders/measurements') }}/${this.selectedUserId}`)
                    .then(res => res.json())
                    .then(data => this.previousMeasurements = data)
                    .catch(() => console.error("Measurement fetch failed"));
            },

            // MODIFIED createCustomer method
            async createCustomer() {
                this.isLoading = true; // Show loading state
                this.customerErrors = {}; // Clear previous errors
                this.customerErrorMessage = ''; // Clear previous error messages

                const fd = new FormData();
                fd.append('name', this.newCustomer.name);
                fd.append('phone', this.newCustomer.phone);
                fd.append('customer_group_id', this.newCustomer.customer_group_id); // <-- NEW LINE: Append customer_group_id

                try {
                    const response = await fetch('{{ url('customer') }}', { // Your `storeCustomer` route
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.getCsrfToken(), // Use the reliable helper function
                            'Accept': 'application/json' // Crucial for receiving JSON validation errors
                        },
                        body: fd,
                    });

                    const data = await response.json();

                    if (!response.ok) { // Check if HTTP status indicates an error (e.g., 400, 422, 500)
                        if (response.status === 422 && data.errors) {
                            // Validation errors from Laravel
                            this.customerErrors = data.errors;
                            this.customerErrorMessage = 'Please correct the highlighted fields.';
                        } else {
                            // Other server errors or API issues
                            this.customerErrorMessage = data.message || 'Could not create customer. An unexpected error occurred.';
                        }
                        return; // Stop execution on error
                    }

                    // Success case
                    console.log('Customer created successfully:', data.user);

                    // Add the newly created customer to the 'customers' array
                    this.customers.push(data.user);

                    // Set the newly created customer as the selected customer in the main form
                    this.selectedUserId = String(data.user.id);
                    this.customerJustChanged = true; // Set flag to trigger clear of items

                    // Reset the modal form and close it
                    this.resetNewCustomerForm();
                    this.showModal = false;

                } catch (error) {
                    console.error('Network or unexpected error:', error);
                    this.customerErrorMessage = 'A network error occurred. Please try again.';
                    this.customerErrors = {}; // Clear field errors on network error
                } finally {
                    this.isLoading = false; // Hide loading state
                }
            },

            createProduct() {
                this.productErrorMessage = '';

                const fd = new FormData();
                fd.append('name', this.newProduct.name);
                fd.append('description', this.newProduct.description);
                fd.append('rate', this.newProduct.rate);
                fd.append('effective_date', this.newProduct.effective_date);
                this.newProduct.measurement_ids.forEach(id => fd.append('measurement_ids[]', id));
                Array.from(this.newProduct.images).forEach(img => fd.append('images[]', img));

                fetch('{{ route('products.store') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.getCsrfToken(), // Use the helper here too!
                            'Accept': 'application/json'
                        },
                        body: fd,
                    })
                    .then(async res => {
                        const data = await res.json();
                        if (!res.ok || !data.id) {
                            this.productErrorMessage = data.message || 'Product creation failed.';
                            return;
                        }

                        // Append to product list
                        this.products.push({
                            id: data.id,
                            name: data.name,
                            current_rate: data.rate,
                            designs: data.designs ?? [],
                            measurements: data.measurements ?? [],
                        });

                        this.selectedProduct = String(data.id);
                        this.showItemModal = false;
                        this.newProduct = {
                            name: '',
                            description: '',
                            rate: '',
                            effective_date: '',
                            measurement_ids: [],
                            images: []
                        };
                    })
                    .catch((err) => {
                        console.error("Product creation failed", err);
                        this.productErrorMessage = 'Network error. Check console for details.';
                    });
            },

            get subtotal() {
                return this.items.reduce((sum, item) => {
                    const qty = parseInt(item.quantity) || 1;
                    const rate = parseFloat(item.rate) || 0;
                    return sum + (qty * rate);
                }, 0);
            },

            get total() {
                return Math.max(0, this.subtotal - parseFloat(this.discount || 0) + parseFloat(this.design_charge ||
                    0) - parseFloat(this.received || 0));
            },

            submitForm() {
                this.$refs.orderForm.submit();
            }
        };
    }
</script>