@extends('layouts.app')
@section('title', 'Add Farmer')
@section('page-title', 'Add New Farmer')

@section('content')
<div class="card" style="max-width:700px;">
    <form data-api-action="{{ url('/api/farmers') }}" data-redirect="{{ route('farmers.index') }}">
        @csrf
        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" name="name" class="form-input" value="{{ old('name') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number *</label>
                <input type="text" name="phone" class="form-input" value="{{ old('phone') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Village</label>
                <input type="text" name="village" class="form-input" value="{{ old('village') }}">
            </div>
            <div class="form-group">
                <label class="form-label">Milk Type *</label>
                <select name="milk_type" class="form-input" required>
                    <option value="cow" {{ old('milk_type') == 'cow' ? 'selected' : '' }}>Cow Milk</option>
                    <option value="buffalo" {{ old('milk_type') == 'buffalo' ? 'selected' : '' }}>Buffalo Milk</option>
                    <option value="a2" {{ old('milk_type') == 'a2' ? 'selected' : '' }}>A2 Premium</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Status *</label>
                <select name="status" class="form-input" required>
                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-input" value="{{ old('address') }}">
            </div>
        </div>

        <div style="margin-top:16px;">
            <h3 style="font-size:15px;font-weight:600;color:#1E293B;margin-bottom:12px;">Bank Details (Optional)</h3>
            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label">Bank Name</label>
                    <input type="text" name="bank_name" class="form-input" value="{{ old('bank_name') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Account Number</label>
                    <input type="text" name="bank_account" class="form-input" value="{{ old('bank_account') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">IFSC Code</label>
                    <input type="text" name="bank_ifsc" class="form-input" value="{{ old('bank_ifsc') }}">
                </div>
            </div>
        </div>

        <div class="form-group" style="margin-top:12px;">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-input" rows="3" style="resize:vertical;">{{ old('notes') }}</textarea>
        </div>

        <div style="margin-top:20px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Farmer</button>
            <a href="{{ route('farmers.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
@endsection
