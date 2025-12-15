<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FaqResource;
use App\Models\Faq;

class FaqController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $faqs = Faq::all();

        return FaqResource::collection($faqs)
            ->additional([
                'status' => 200,
                'message' => 'FAQs retrieved successfully.',
            ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Faq $faq)
    {
        return FaqResource::make($faq)
            ->additional([
                'status' => 200,
                'message' => 'FAQ retrieved successfully.',
            ]);
    }
}
