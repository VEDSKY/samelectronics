<?php

namespace App\Http\Controllers;

use App\Models\Releases;
use App\Http\Requests\StoreReleasesRequest;
use App\Http\Requests\UpdateReleasesRequest;

class ReleasesController extends Controller
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
    public function store(StoreReleasesRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Releases $releases)
    {
        $pageData = [
            'continued_message' => 'ты можешь послушать наши релизы на бендкампе.',
            'action_button_text' => 'бендкамп',
            'action_button_url' => 'https://samelectronics.bandcamp.com'
        ];
        return view('sorry', $pageData);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Releases $releases)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReleasesRequest $request, Releases $releases)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Releases $releases)
    {
        //
    }
}
