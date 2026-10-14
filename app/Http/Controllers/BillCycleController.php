<?php

namespace App\Http\Controllers;

use App\Models\BillCycle;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BillCycleController extends Controller
{
    public function index()
    {
        $cycles = BillCycle::orderBy('start_date', 'desc')->get();
        $currentCycle = BillCycle::where('status', 'active')->latest()->first();
        return view('bill-cycles.index', compact('cycles', 'currentCycle'));
    }

 public function store(Request $request)
{
    $validated = $request->validate([
        'cycle_type'            => 'required|in:10_days,15_days,custom',
        'start_date'            => 'required|date',
        'end_date'              => 'required|date|after:start_date',
        'auto_generate_invoice' => 'boolean',
        'sms_on_invoice'        => 'boolean',
    ]);

    $validated['name'] = Carbon::parse($validated['start_date'])->format('M Y') . ' — Cycle';
    $validated['status'] = 'active';

    BillCycle::where('status', 'active')->update([
        'status' => 'completed'
    ]);

    BillCycle::create($validated);

    return redirect()->route('bill-cycles.index')
        ->with('success', 'Bill cycle created.');
}

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'cycle_type'            => 'required|in:10_days,15_days,custom',
            'auto_generate_invoice' => 'boolean',
            'sms_on_invoice'        => 'boolean',
        ]);

        // Update the current active cycle
        $cycle = BillCycle::where('status', 'active')->latest()->first();
        if ($cycle) {
            $cycle->update($validated);
        }

        return redirect()->back()->with('success', 'Settings updated.');
    }
}
