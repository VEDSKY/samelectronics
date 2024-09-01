<?php

namespace App\Http\Controllers;

use App\Models\Sorry;
use App\Http\Requests\StoreSorryRequest;
use App\Http\Requests\UpdateSorryRequest;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;

class SorryController extends Controller
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
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSorryRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Sorry $sorry): Factory|View|Application
    {
        return view('sorry');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Sorry $sorry)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSorryRequest $request, Sorry $sorry)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sorry $sorry)
    {
        //
    }
}
