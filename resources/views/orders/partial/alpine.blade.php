<script>
    function orderForm(isEdit = false, rawData = {}) {

        const products = @json($productsJson);

        const oldItems = @json(old('items') ?? ($order->items ?? []));
        const customers = window.initialCustomers = Array.from(
            new Map(@json($customers).map(c => [c.id, c])).values()
        );

        const customerGroups = @json($customerGroups ?? []);

        return {
            customerJustChanged: false,
            isEdit,
            selectedUserId: @json(old('user_id') ?? ($order->user_id ?? '')),
            selectedCustomerId: @json(old('customer_id') ?? ($order->customer_id ?? '')),
            measurements: @json($measurements->map(fn($m) => ['id' => $m->id, 'name' => $m->name])),
            selectedProduct: '',
            products,
            customers,
            customerGroups,
            discount: {{ old('discount', $order->discount ?? 0) }},
            design_charge: {{ old('design_charge', $order->design_charge ?? 0) }},
            received: {{ old('received', $order->received ?? 0) }},
            showModal: false, // For customer creation modal
            newCustomer: {
                name: '',
                phone: '',
                customer_group_id: customerGroups.length > 0 ? customerGroups[0].id : ''
            },
            customerErrors: {},
            customerErrorMessage: '',
            isLoading: false,

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

            getCsrfToken() {
                const tokenElement = document.head.querySelector('meta[name="csrf-token"]');
                return tokenElement ? tokenElement.content : '';
            },

            resetNewCustomerForm() {
                this.newCustomer = {
                    name: '',
                    phone: '',
                    customer_group_id: this.customerGroups.length > 0 ? this.customerGroups[0].id : ''
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

                this.$watch('selectedUserId', () => {
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
                        this.customerJustChanged = false;
                    } else if (this.selectedUserId === '') {
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

                if (this.isEdit && this.selectedUserId) {
                    this.fetchPreviousMeasurements();
                }
            },

            addItem() {
                if (!this.selectedUserId) {
                    alert("Select a customer first.");
                    return;
                }

                const prod = this.products.find(p => p.id == this.selectedProduct);
                if (!prod) return;

                // Check if the item already exists
                const existing = this.items.find(i => i.id == this.selectedProduct);
                if (existing) {
                    // Item exists, allow adding but show a warning
                    if (!confirm("This product is already in the list. Do you want to add it again?")) {
                        // this.selectedProduct = ''; // Clear selection if user cancels
                        return; // Stop execution if user cancels
                    }
                    // If user confirms, continue to add the item (no 'return' here)
                }

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
                    // Add a property to control collapse state for new items
                    // New items collapse if there are already existing items (before adding this one)
                    isCollapsed: this.items.length > 0
                });

                this.selectedProduct = ''; // Clear the product selection after adding
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

            async createCustomer() {
                this.isLoading = true;
                this.customerErrors = {};
                this.customerErrorMessage = '';

                const fd = new FormData();
                fd.append('name', this.newCustomer.name);
                fd.append('phone', this.newCustomer.phone);
                fd.append('customer_group_id', this.newCustomer.customer_group_id);

                try {
                    const response = await fetch('{{ url('customer') }}', { // Your `storeCustomer` route
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.getCsrfToken(),
                            'Accept': 'application/json'
                        },
                        body: fd,
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        if (response.status === 422 && data.errors) {
                            this.customerErrors = data.errors;
                            this.customerErrorMessage = 'Please correct the highlighted fields.';
                        } else {
                            this.customerErrorMessage = data.message || 'Could not create customer. An unexpected error occurred.';
                        }
                        return;
                    }

                    console.log('Customer created successfully:', data.user);

                    this.customers.push(data.user);

                    this.selectedUserId = String(data.user.id);
                    this.customerJustChanged = true;

                    this.resetNewCustomerForm();
                    this.showModal = false;

                } catch (error) {
                    console.error('Network or unexpected error:', error);
                    this.customerErrorMessage = 'A network error occurred. Please try again.';
                    this.customerErrors = {};
                } finally {
                    this.isLoading = false;
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
                            'X-CSRF-TOKEN': this.getCsrfToken(),
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