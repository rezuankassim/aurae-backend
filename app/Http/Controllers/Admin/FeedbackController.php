<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Inertia\Inertia;

class FeedbackController extends Controller
{
    public function index()
    {
        $feedbacks = Feedback::with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('admin/feedbacks/index', [
            'feedbacks' => $feedbacks,
        ]);
    }

    public function show(Feedback $feedback)
    {
        $feedback->load('user:id,name,email');

        return Inertia::render('admin/feedbacks/show', [
            'feedback' => $feedback,
        ]);
    }
}
