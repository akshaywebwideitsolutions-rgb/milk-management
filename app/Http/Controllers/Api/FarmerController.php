<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use Illuminate\Http\Request;

class FarmerController extends Controller
{
    public function index(Request $request)
    {
        $query = Farmer::query()->withTrashed();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('farmer_id', 'like', "%{$search}%");
            });
        }

        if ($milkType = $request->query('milk_type')) {
            $query->where('milk_type', $milkType);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return response()->json($query->orderByDesc('id')->paginate($request->integer('per_page', 15)));
    }

    public function store(Request $request)
    {
        $farmer = Farmer::create($this->validated($request));

        return response()->json([
            'message' => 'Farmer added successfully.',
            'data' => $farmer,
        ], 201);
    }

    public function show(Farmer $farmer)
    {
        return response()->json(['data' => $farmer]);
    }

    public function update(Request $request, Farmer $farmer)
    {
        $farmer->update($this->validated($request, $farmer->id));

        return response()->json([
            'message' => 'Farmer updated successfully.',
            'data' => $farmer->refresh(),
        ]);
    }

    public function destroy(Farmer $farmer)
    {
        $farmer->delete();

        return response()->json(['message' => 'Farmer deleted.']);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'required|string|max:15|unique:farmers,phone' . ($ignoreId ? ",{$ignoreId}" : ''),
            'village' => 'nullable|string|max:100',
            'milk_type' => 'required|in:cow,buffalo,a2',
            'status' => 'required|in:active,inactive',
            'address' => 'nullable|string|max:300',
            'bank_name' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:30',
            'bank_ifsc' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:500',
        ]);
    }
}
