<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillCycle;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillCycleController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => [
                'cycles' => BillCycle::orderByDesc('start_date')->get(),
                'current_cycle' => BillCycle::where('status', 'active')->latest()->first(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cycle_type' => 'required|in:10_days,15_days,custom',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'auto_generate_invoice' => 'boolean',
            'sms_on_invoice' => 'boolean',
        ]);

        $validated['name'] = Carbon::parse($validated['start_date'])->format('M Y').' - Cycle';
        $validated['status'] = 'active';

        $cycle = DB::transaction(function () use ($validated) {
            // Serialize cycle creation, including when there are no existing cycles.
            User::where('is_admin', true)->orderBy('id')->lockForUpdate()->get();
            if (BillCycle::where('start_date', '<=', $validated['end_date'])->where('end_date', '>=', $validated['start_date'])->exists()) {
                throw ValidationException::withMessages(['start_date' => 'This period overlaps an existing bill cycle.']);
            }
            BillCycle::where('status', 'active')->update(['status' => 'completed']);

            return BillCycle::create($validated);
        });

        return response()->json(['message' => 'Bill cycle created.', 'data' => $cycle], 201);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'cycle_type' => 'required|in:10_days,15_days,custom',
            'auto_generate_invoice' => 'boolean',
            'sms_on_invoice' => 'boolean',
        ]);

        $cycle = BillCycle::where('status', 'active')->latest()->firstOrFail();
        $cycle->update($validated);

        return response()->json(['message' => 'Settings updated.', 'data' => $cycle->refresh()]);
    }
}
