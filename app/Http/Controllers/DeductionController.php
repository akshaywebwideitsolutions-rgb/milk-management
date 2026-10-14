<?php

namespace App\Http\Controllers;

use App\Models\BillCycle;
use App\Models\Deduction;
use App\Models\Farmer;
use Illuminate\Http\Request;

class DeductionController extends Controller
{
    public function index(Request $request)
    {
        $query = Deduction::with(['farmer', 'billCycle', 'createdBy']);

        if ($cycleId = $request->get('bill_cycle_id')) {
            $query->where('bill_cycle_id', $cycleId);
        }

        $deductions = $query->orderBy('deduction_date', 'desc')->paginate(20)->withQueryString();
        $cycles = BillCycle::orderBy('start_date', 'desc')->get();
        $farmers = Farmer::where('status', 'active')->orderBy('name')->get();

        $totalThisCycle = Deduction::when($cycleId, fn($q) => $q->where('bill_cycle_id', $cycleId))
            ->where('status', 'applied')
            ->sum('amount');

        return view('deductions.index', compact('deductions', 'cycles', 'farmers', 'totalThisCycle'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'farmer_id'      => 'required|exists:farmers,id',
            'bill_cycle_id'  => 'required|exists:bill_cycles,id',
            'deduction_type' => 'required|string|max:100',
            'amount'         => 'required|numeric|min:0',
            'deduction_date' => 'required|date',
            'description'    => 'nullable|string|max:300',
        ]);

        $validated['status'] = 'applied';
        $validated['created_by'] = auth()->id();

        Deduction::create($validated);

        return redirect()->back()->with('success', 'Deduction added.');
    }

    public function update(Request $request, Deduction $deduction)
    {
        $validated = $request->validate([
            'deduction_type' => 'required|string|max:100',
            'amount'         => 'required|numeric|min:0',
            'deduction_date' => 'required|date',
            'status'         => 'required|in:pending,applied',
            'description'    => 'nullable|string|max:300',
        ]);

        $deduction->update($validated);
        return redirect()->back()->with('success', 'Deduction updated.');
    }

    public function destroy(Deduction $deduction)
    {
        $deduction->delete();
        return redirect()->back()->with('success', 'Deduction deleted.');
    }
}
