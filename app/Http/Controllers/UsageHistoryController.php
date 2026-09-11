<?php

namespace App\Http\Controllers;

use App\Models\UsageHistory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UsageHistoryController extends Controller
{
    public function index()
    {
        $usage_histories = UsageHistory::where('user_id', auth()->id())
            ->with('therapy:id,name')
            ->latest()
            ->get();

        return Inertia::render('usage-history/index', [
            'usage_histories' => $usage_histories,
        ]);
    }

    public function show(UsageHistory $usageHistory)
    {
        abort_if($usageHistory->user_id !== auth()->id(), 403);

        $usageHistory->load('therapy:id,name,image');
        if ($usageHistory->therapy) {
            $usageHistory->therapy->image_url = $usageHistory->therapy->image_url;
        }

        return Inertia::render('usage-history/show', [
            'usageHistory' => $usageHistory,
        ]);
    }

    public function edit(UsageHistory $usageHistory) {}

    public function update(Request $request, UsageHistory $usageHistory) {}

    public function destroy(UsageHistory $usageHistory) {}
}
