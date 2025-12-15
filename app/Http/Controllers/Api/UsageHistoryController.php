<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Models\UsageHistory;
use Illuminate\Http\Request;

class UsageHistoryController extends Controller
{
    /**
     * Store a newly created usage history in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'therapy_id' => ['required', 'exists:therapies,id'],
            'duration' => ['nullable', 'numeric', 'min:0'],
            'force_stopped' => ['nullable', 'boolean'],
            'started_at' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date'],
        ]);

        $content = [
            'duration' => $request->input('duration'),
            'force_stopped' => $request->input('force_stopped', false),
            'started_at' => $request->input('started_at'),
            'ended_at' => $request->input('ended_at'),
        ];

        $usageHistory = UsageHistory::create([
            'user_id' => $request->user()->id,
            'therapy_id' => $request->input('therapy_id'),
            'content' => $content,
        ]);

        return BaseResource::make($usageHistory)
            ->additional([
                'status' => 201,
                'message' => 'Usage history created successfully.',
            ]);
    }
}
