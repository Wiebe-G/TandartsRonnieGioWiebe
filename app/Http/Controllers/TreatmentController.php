<?php

namespace App\Http\Controllers;

use App\Models\Treatment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TreatmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Treatments/Create', [

        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'Name' => ['required', 'string', 'max:255'],
            'Description' => ['required', 'string', 'max:255'],
            'Price' => ['required', 'numeric'],
            'DurationInMinutes' => ['required', 'numeric'],
        ]);

        Treatment::create([
            'name' => $validated['Name'],
            'description' => $validated['Description'],
            'price' => $validated['Price'],
            'duration' => $validated['DurationInMinutes'],
            'active' => true,
        ]);

        return redirect()->route('treatments')->with('success', 'Behandeling aangemaakt!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
