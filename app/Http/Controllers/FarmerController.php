<?php

namespace App\Http\Controllers;

use App\Models\Farmer;
use Illuminate\Http\Request;

class FarmerController extends Controller
{
    public function index(Request $request)
    {
        $query = Farmer::query()->withTrashed();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('farmer_id', 'like', "%{$search}%");
            });
        }

        if ($milkType = $request->get('milk_type')) {
            $query->where('milk_type', $milkType);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $farmers = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('farmers.index', compact('farmers'));
    }

    public function create()
    {
        return view('farmers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:150',
            'phone'        => 'required|string|max:15|unique:farmers,phone',
            'village'      => 'nullable|string|max:100',
            'milk_type'    => 'required|in:cow,buffalo,a2',
            'status'       => 'required|in:active,inactive',
            'address'      => 'nullable|string|max:300',
            'bank_name'    => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:30',
            'bank_ifsc'    => 'nullable|string|max:20',
            'notes'        => 'nullable|string|max:500',
        ]);

        Farmer::create($validated);
        return redirect()->route('farmers.index')->with('success', 'Farmer added successfully.');
    }

    public function edit(Farmer $farmer)
    {
        return view('farmers.edit', compact('farmer'));
    }

    public function update(Request $request, Farmer $farmer)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:150',
            'phone'        => 'required|string|max:15|unique:farmers,phone,' . $farmer->id,
            'village'      => 'nullable|string|max:100',
            'milk_type'    => 'required|in:cow,buffalo,a2',
            'status'       => 'required|in:active,inactive',
            'address'      => 'nullable|string|max:300',
            'bank_name'    => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:30',
            'bank_ifsc'    => 'nullable|string|max:20',
            'notes'        => 'nullable|string|max:500',
        ]);

        $farmer->update($validated);
        return redirect()->route('farmers.index')->with('success', 'Farmer updated successfully.');
    }

    public function destroy(Farmer $farmer)
    {
        $farmer->delete();
        return redirect()->route('farmers.index')->with('success', 'Farmer deleted.');
    }
}
