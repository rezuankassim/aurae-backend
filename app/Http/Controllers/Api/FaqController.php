<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FaqResource;
use App\Models\Faq;

class FaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::query()
            ->where('status', 1)
            ->get();

        return FaqResource::collection($faqs)
            ->additional([
                'status' => 200,
                'message' => 'FAQs retrieved successfully.',
            ]);
    }

    public function show(Faq $faq)
    {
        return FaqResource::make($faq)
            ->additional([
                'status' => 200,
                'message' => 'FAQ retrieved successfully.',
            ]);
    }
}
