@extends('layouts.main')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Profile Details</h2>
           <a href="{{ route('profile.edit', $user) }}" class="btn btn-warning">
    <i class="fa-solid fa-pen-to-square me-1"></i> Edit Profile
</a>
        </div>

        <div class="card shadow-sm p-3">
            <div class="d-flex align-items-center mb-3 gap-4">
                <img src="{{ $user->profile_picture ? asset($user->profile_picture) : asset('images/default-user.svg') }}"
                    alt="Profile Picture" class="rounded-circle shadow" width="80" height="80">
                <div>
                    <h5 class="mb-1">{{ $user->name }}</h5>
                    <p class="mb-0 text-muted">{{ $user->email }}</p>
                    <p class="mb-0 text-muted">{{ $user->phone }}</p>
                    @if ($user->is_active == 1)
                        <p class="mb-0 badge bg-success">Active</p>
                    @else
                        <p class="mb-0 badge bg-secondary">Inactive</p>
                    @endif
                </div>
            </div>

            <hr>

            <p><strong>Role:</strong> {{ ucfirst($user->role) }}</p>
        </div>
        <div class="card" x-data="customerCommunications({{ $user->id }})">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Customer Communications Log</h5>
            @can('create', [\App\Models\CustomerCommunication::class, $user]) {{-- Check policy for creating communications for this customer --}}
                <button class="btn btn-sm btn-success" @click="showAddForm = true; resetForm()">
                    <i class="fa fa-plus me-2"></i> Log New Communication
                </button>
            @endcan
        </div>
        <div class="card-body">

            {{-- Loading and Error Messages --}}
            <div x-show="isLoading" class="alert alert-info text-center">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Loading communications...
            </div>
            <div x-show="generalError" class="alert alert-danger" x-text="generalError"></div>
            <div x-show="successMessage" class="alert alert-success" x-text="successMessage"></div>

            {{-- Form to Add/Edit Communication --}}
            <template x-if="showAddForm || showEditForm">
                <div class="mb-4 p-3 border rounded bg-light">
                    <h6 x-text="showAddForm ? 'Add New Communication' : 'Edit Communication'"></h6>
                    <form @submit.prevent="saveCommunication">
                        @csrf {{-- Laravel CSRF token --}}

                        <div class="mb-3">
                            <label for="type" class="form-label">Type</label>
                            <select x-model="form.type" id="type" class="form-select" :class="{'is-invalid': formErrors.type}">
                                <option value="">Select Type</option>
                                @foreach(\App\Models\CustomerCommunication::getTypes() as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                            <template x-if="formErrors.type">
                                <div class="invalid-feedback" x-text="formErrors.type[0]"></div>
                            </template>
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject (Optional)</label>
                            <input type="text" x-model="form.subject" id="subject" class="form-control" :class="{'is-invalid': formErrors.subject}">
                            <template x-if="formErrors.subject">
                                <div class="invalid-feedback" x-text="formErrors.subject[0]"></div>
                            </template>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Content</label>
                            <textarea x-model="form.content" id="content" class="form-control" rows="3" :class="{'is-invalid': formErrors.content}"></textarea>
                            <template x-if="formErrors.content">
                                <div class="invalid-feedback" x-text="formErrors.content[0]"></div>
                            </template>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-secondary me-2" @click="cancelForm">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="formIsSaving">
                                <span x-show="!formIsSaving" x-text="showAddForm ? 'Add Log' : 'Update Log'"></span>
                                <span x-show="formIsSaving">
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    <span x-text="showAddForm ? 'Adding...' : 'Updating...'"></span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </template>

            {{-- No Communications Message --}}
            <template x-if="!isLoading && communications.length === 0 && !generalError && !showAddForm && !showEditForm">
                <div class="alert alert-info text-center">No communications logged for this customer yet.</div>
            </template>

            {{-- List of Communications --}}
            <div x-show="communications.length > 0">
                <ul class="list-group">
                    <template x-for="comm in communications" :key="comm.id">
                        <li class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-2 shadow-sm rounded">
                            <div class="flex-grow-1">
                                <h6 class="mb-1" x-text="`${comm.type}: ${comm.subject || 'No Subject'}`"></h6>
                                <p class="mb-1 text-muted small">
                                    Logged by <span x-text="comm.logged_by_user.name"></span> on <span x-text="new Date(comm.created_at).toLocaleString()"></span>
                                </p>
                                <p x-text="comm.content" class="mb-0"></p>
                            </div>
                            <div class="mt-2 mt-md-0 d-flex flex-shrink-0">
                                @can('update', [\App\Models\CustomerCommunication::class, new \App\Models\CustomerCommunication()]) {{-- Check policy for updating ANY communication. This needs to be refined for specific comm. --}}
                                    <button class="btn btn-sm btn-info me-2" @click="editCommunication(comm)" title="Edit">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                @endcan
                                @can('delete', [\App\Models\CustomerCommunication::class, new \App\Models\CustomerCommunication()]) {{-- Check policy for deleting ANY communication. This needs to be refined for specific comm. --}}
                                    <button class="btn btn-sm btn-danger" @click="deleteCommunication(comm.id)" title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                @endcan
                            </div>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
    </div>
    </div>
    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('customerCommunications', (customerId) => ({
            customerId: customerId,
            communications: [],
            isLoading: true,
            generalError: null,
            successMessage: null,

            showAddForm: false,
            showEditForm: false,
            editingCommunicationId: null,
            form: {
                type: '',
                subject: '',
                content: '',
            },
            formErrors: {},
            formIsSaving: false,

            init() {
                this.fetchCommunications();
            },

            async fetchCommunications() {
                this.isLoading = true;
                this.generalError = null;
                try {
                    const response = await fetch(`/customers/${this.customerId}/communications`); // Use your web route
                    if (!response.ok) {
                        const errorData = await response.json();
                        throw new Error(errorData.message || 'Failed to fetch communications.');
                    }
                    const data = await response.json();
                    this.communications = data.communications;
                } catch (error) {
                    this.generalError = error.message;
                    console.error('Error fetching communications:', error);
                } finally {
                    this.isLoading = false;
                }
            },

            resetForm() {
                this.form = {
                    type: '',
                    subject: '',
                    content: '',
                };
                this.formErrors = {};
                this.editingCommunicationId = null;
                this.formIsSaving = false;
                this.generalError = null; // Clear general error when opening form
                this.successMessage = null; // Clear success message when opening form
            },

            cancelForm() {
                this.showAddForm = false;
                this.showEditForm = false;
                this.resetForm();
            },

            async saveCommunication() {
                this.formIsSaving = true;
                this.generalError = null;
                this.formErrors = {};
                this.successMessage = null;

                const url = this.showAddForm
                    ? `/customers/${this.customerId}/communications`
                    : `/customers/${this.customerId}/communications/${this.editingCommunicationId}`; // Use the non-nested route for update/delete
                const method = this.showAddForm ? 'POST' : 'PUT';

                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                        body: JSON.stringify(this.form),
                    });

                    if (response.status === 422) { // Validation errors
                        const errorData = await response.json();
                        this.formErrors = errorData.errors;
                        this.generalError = errorData.message; // Often "The given data was invalid."
                    } else if (!response.ok) { // Other server errors
                        const errorData = await response.json();
                        throw new Error(errorData.message || 'An unexpected error occurred.');
                    } else { // Success
                        const data = await response.json();
                        if (this.showAddForm) {
                            this.communications.unshift(data.communication); // Add new communication to the top
                            this.successMessage = data.message;
                        } else {
                            // Find and update the communication in the list
                            const index = this.communications.findIndex(comm => comm.id === this.editingCommunicationId);
                            if (index !== -1) {
                                this.communications[index] = data.communication;
                                this.successMessage = data.message;
                            }
                        }
                        this.cancelForm(); // Close form and reset
                        // This timeout is optional, just to show the message briefly
                        setTimeout(() => this.successMessage = null, 3000);
                    }
                } catch (error) {
                    this.generalError = error.message;
                    console.error('Error saving communication:', error);
                } finally {
                    this.formIsSaving = false;
                }
            },

            editCommunication(comm) {
                this.resetForm(); // Clear previous form state
                this.form.type = comm.type;
                this.form.subject = comm.subject;
                this.form.content = comm.content;
                this.editingCommunicationId = comm.id;
                this.showEditForm = true;
                this.showAddForm = false; // Ensure add form is hidden
            },

            async deleteCommunication(commId) {
                if (!confirm('Are you sure you want to delete this communication log?')) {
                    return;
                }

                this.isLoading = true; // Show loading state for list
                this.generalError = null;
                this.successMessage = null;

                try {
                    const response = await fetch(`/customers/${this.customerId}/communications/${commId}`, { // Use the non-nested route for update/delete
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                    });

                    if (!response.ok) {
                        const errorData = await response.json();
                        throw new Error(errorData.message || 'Failed to delete communication.');
                    }

                    const data = await response.json();
                    this.communications = this.communications.filter(comm => comm.id !== commId); // Remove from list
                    this.successMessage = data.message;
                    setTimeout(() => this.successMessage = null, 3000);
                } catch (error) {
                    this.generalError = error.message;
                    console.error('Error deleting communication:', error);
                } finally {
                    this.isLoading = false;
                }
            },
        }));
    });
</script>
@endsection