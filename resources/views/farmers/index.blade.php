@extends('layouts.app')
@section('title', 'Farmer Management')
@section('page-title', 'Farmer Management')

@section('content')
<div class="page-header-bar">
    <form method="GET" action="{{ route('farmers.index') }}" class="filter-bar">
        <input type="text" name="search" class="form-input" placeholder="Search by name, phone, or ID..." value="{{ request('search') }}" style="max-width:300px;">
        <select name="milk_type" class="form-input" style="width:150px;">
            <option value="">Milk Type: All</option>
            <option value="cow" {{ request('milk_type') == 'cow' ? 'selected' : '' }}>Cow</option>
            <option value="buffalo" {{ request('milk_type') == 'buffalo' ? 'selected' : '' }}>Buffalo</option>
            <option value="a2" {{ request('milk_type') == 'a2' ? 'selected' : '' }}>A2 Premium</option>
        </select>
        <select name="status" class="form-input" style="width:140px;">
            <option value="">Status: All</option>
            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <button type="submit" class="btn btn-outline"><i class="fas fa-filter"></i> Filter</button>
    </form>
    <a href="{{ route('farmers.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add Farmer</a>
</div>

<div class="card" style="margin-top:16px;">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Farmer Name</th>
                    <th>Phone</th>
                    <th>Village</th>
                    <th>Milk Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="farmers-table-body">
                <tr><td colspan="7" style="text-align:center;padding:40px;color:#94A3B8;">Loading farmers...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="table-footer" id="farmers-table-footer">
        <span class="table-info">Loading farmers...</span>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const tbody = document.getElementById('farmers-table-body');
    const footer = document.getElementById('farmers-table-footer');
    const params = new URLSearchParams(window.location.search);
    const milkLabel = (value) => ({ cow: 'Cow Milk', buffalo: 'Buffalo Milk', a2: 'A2 Premium' }[value] || value || '-');

    try {
        const response = await DairyApp.api(`/api/farmers?${params.toString()}`);
        const page = response.data?.data ? response.data : response;
        const farmers = page.data || [];

        tbody.innerHTML = farmers.length
            ? farmers.map((farmer) => {
                const milkType = farmer.milk_type?.value || farmer.milk_type;
                const status = farmer.status?.value || farmer.status;
                const statusClass = status === 'active' ? 'badge-green' : 'badge-gray';

                return `
                    <tr>
                        <td><strong>${farmer.farmer_id}</strong></td>
                        <td>${farmer.name}</td>
                        <td>${farmer.phone}</td>
                        <td>${farmer.village || '-'}</td>
                        <td><span class="milk-type-badge milk-type-${milkType}">${milkLabel(milkType)}</span></td>
                        <td><span class="badge-status ${statusClass}">${status}</span></td>
                        <td>
                            <a href="/farmers/${farmer.id}/edit" class="btn-icon" title="Edit"><i class="fas fa-edit"></i></a>
                            <button type="button" class="btn-icon btn-icon-danger" title="Delete" data-delete-farmer="${farmer.id}"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            }).join('')
            : '<tr><td colspan="7" style="text-align:center;padding:40px;color:#94A3B8;">No farmers found</td></tr>';

        footer.innerHTML = `<span class="table-info">Showing ${page.from || 0}-${page.to || 0} of ${page.total || 0} farmers</span>`;
    } catch (error) {
        DairyApp.flash(error.message, 'error');
    }

    document.querySelectorAll('[data-delete-farmer]').forEach((button) => {
        button.addEventListener('click', async () => {
            if (!confirm('Delete this farmer?')) return;
            try {
                await DairyApp.api(`/api/farmers/${button.dataset.deleteFarmer}`, { method: 'DELETE' });
                window.location.reload();
            } catch (error) {
                DairyApp.flash(error.message, 'error');
            }
        });
    });
});
</script>
@endpush
