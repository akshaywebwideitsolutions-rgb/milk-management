@extends('layouts.app')
@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
    {{-- SMS / DLT Settings --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">SMS &amp; DLT Configuration</h3></div>
        <div class="form-group">
            <label class="form-label">SMS Gateway URL</label>
            <input type="text" class="form-input" value="{{ config('sms.gateway_url') }}" readonly style="background:#F8FAFC;">
        </div>
        <div class="form-group">
            <label class="form-label">API Key</label>
            <input type="password" class="form-input" value="••••••••" readonly style="background:#F8FAFC;">
            <p style="font-size:11px;color:#94A3B8;margin-top:4px;">Configure in <code>.env</code> file — SMS_API_KEY</p>
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label">Sender ID</label>
                <input type="text" class="form-input" value="{{ config('sms.sender_id') }}" readonly style="background:#F8FAFC;">
            </div>
            <div class="form-group">
                <label class="form-label">DLT Entity ID</label>
                <input type="text" class="form-input" value="{{ config('sms.dlt_entity_id') }}" readonly style="background:#F8FAFC;">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">DLT Template ID</label>
            <input type="text" class="form-input" value="{{ config('sms.dlt_template_id') }}" readonly style="background:#F8FAFC;">
        </div>
        <div style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px;margin-top:8px;">
            <p style="font-size:12px;color:#065F46;"><i class="fas fa-info-circle"></i> SMS settings are configured via environment variables. Edit the <code>.env</code> file to update.</p>
        </div>
    </div>

    {{-- System Settings --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">System Information</h3></div>
        <div class="settings-info-grid">
            <div class="settings-info-item">
                <span class="settings-info-label">Application</span>
                <span class="settings-info-value">Jay Malhar Dairy v1.0</span>
            </div>
            <div class="settings-info-item">
                <span class="settings-info-label">Framework</span>
                <span class="settings-info-value">Laravel 11</span>
            </div>
            <div class="settings-info-item">
                <span class="settings-info-label">PHP Version</span>
                <span class="settings-info-value">8.2+</span>
            </div>
            <div class="settings-info-item">
                <span class="settings-info-label">Database</span>
                <span class="settings-info-value">MySQL</span>
            </div>
            <div class="settings-info-item">
                <span class="settings-info-label">Environment</span>
                <span class="settings-info-value">{{ app()->environment() }}</span>
            </div>
            <div class="settings-info-item">
                <span class="settings-info-label">Current Cycle</span>
                <span class="settings-info-value">{{ $currentCycle ? $currentCycle->name : 'None active' }}</span>
            </div>
        </div>

        <div style="margin-top:24px;">
            <h4 style="font-size:14px;font-weight:600;margin-bottom:12px;">Quick Actions</h4>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <a href="{{ route('milk-rates.index') }}" class="btn btn-outline" style="justify-content:flex-start;"><i class="fas fa-rupee-sign" style="width:20px;"></i> Manage Milk Rates</a>
                <a href="{{ route('bill-cycles.index') }}" class="btn btn-outline" style="justify-content:flex-start;"><i class="fas fa-calendar" style="width:20px;"></i> Bill Cycle Settings</a>
                <a href="{{ route('farmers.create') }}" class="btn btn-outline" style="justify-content:flex-start;"><i class="fas fa-user-plus" style="width:20px;"></i> Add New Farmer</a>
            </div>
        </div>
    </div>
</div>
@endsection
