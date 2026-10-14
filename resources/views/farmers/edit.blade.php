@extends('layouts.app')
@section('title', 'Edit Farmer')
@section('page-title', 'Edit Farmer')

@section('content')
<div class="card" style="max-width:700px;">
    <form id="farmer-edit-form" data-api-action="" data-api-method="PUT" data-redirect="{{ route('farmers.index') }}">
        @csrf
        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" name="name" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number *</label>
                <input type="text" name="phone" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Village</label>
                <input type="text" name="village" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Milk Type *</label>
                <select name="milk_type" class="form-input" required>
                    <option value="cow">Cow Milk</option>
                    <option value="buffalo">Buffalo Milk</option>
                    <option value="a2">A2 Premium</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Status *</label>
                <select name="status" class="form-input" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-input">
            </div>
        </div>

        <div style="margin-top:16px;">
            <h3 style="font-size:15px;font-weight:600;color:#1E293B;margin-bottom:12px;">Bank Details</h3>
            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label">Bank Name</label>
                    <input type="text" name="bank_name" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Account Number</label>
                    <input type="text" name="bank_account" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">IFSC Code</label>
                    <input type="text" name="bank_ifsc" class="form-input">
                </div>
            </div>
        </div>

        <div class="form-group" style="margin-top:12px;">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-input" rows="3" style="resize:vertical;"></textarea>
        </div>

        <div style="margin-top:20px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Farmer</button>
            <a href="{{ route('farmers.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const id = window.location.pathname.match(/\/farmers\/(\d+)\/edit/)?.[1];
    const form = document.getElementById('farmer-edit-form');
    if (!id || !form) return;

    form.dataset.apiAction = `/api/farmers/${id}`;

    try {
        const response = await DairyApp.api(`/api/farmers/${id}`);
        const farmer = response.data;

        Object.entries(farmer).forEach(([key, value]) => {
            const field = form.elements[key];
            if (!field || value === null || typeof value === 'object') return;
            field.value = value;
        });

        if (farmer.milk_type?.value) form.elements.milk_type.value = farmer.milk_type.value;
        if (farmer.status?.value) form.elements.status.value = farmer.status.value;
    } catch (error) {
        DairyApp.flash(error.message, 'error');
    }
});
</script>
@endpush
