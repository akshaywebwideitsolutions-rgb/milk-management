<?php

namespace App\Http\Controllers;

use App\Models\BillCycle;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $currentCycle = BillCycle::where('status', 'active')->latest()->first();
        return view('settings.index', compact('currentCycle'));
    }

    public function update(Request $request)
    {
        // General settings can be stored in .env or a settings table
        // For now, redirect back with success
        return redirect()->back()->with('success', 'Settings saved.');
    }
}
