<?php

namespace App\Http\Controllers;

use App\Models\HomePage;
use App\Http\Requests\StoreHomePageRequest;
use App\Http\Requests\UpdateHomePageRequest;

class HomePageController extends Controller
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
    public function store(StoreHomePageRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(HomePage $homePage)
    {
        return view('welcome');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(HomePage $homePage)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateHomePageRequest $request, HomePage $homePage)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HomePage $homePage)
    {
        //
    }
}
