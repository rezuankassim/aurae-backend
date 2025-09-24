<?php

namespace App\Http\Controllers;

use App\Models\UsageHistory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UsageHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $usage_histories = UsageHistory::where('user_id', auth()->id())->latest()->get();

        return Inertia::render('usage-history/index', [
            'usage_histories' => $usage_histories,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(UsageHistory $usageHistory)
    {
        abort_if($usageHistory->user_id !== auth()->id(), 403);

        return Inertia::render('usage-history/show', [
            'usageHistory' => $usageHistory,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UsageHistory $usageHistory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UsageHistory $usageHistory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UsageHistory $usageHistory)
    {
        //
    }
}
