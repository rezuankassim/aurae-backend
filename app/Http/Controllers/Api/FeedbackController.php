<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Store a newly created feedback in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'description' => ['required', 'string'],
        ]);

        $feedback = Feedback::create([
            'user_id' => $request->user()?->id,
            'description' => $request->input('description'),
        ]);

        return BaseResource::make($feedback)
            ->additional([
                'status' => 200,
                'message' => 'Feedback submitted successfully.',
            ]);
    }
}
