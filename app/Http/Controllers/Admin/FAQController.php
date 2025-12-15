<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FaqCreateRequest;
use App\Http\Requests\Admin\FaqUpdateRequest;
use App\Models\Faq;
use Inertia\Inertia;

class FAQController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $faqs = Faq::all();

        return Inertia::render('admin/faqs/index', [
            'faqs' => $faqs,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admin/faqs/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FaqCreateRequest $request)
    {
        $validated = $request->validated();

        Faq::create($validated);

        return to_route('admin.faqs.index')->with('success', 'FAQ created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Faq $faq)
    {
        return Inertia::render('admin/faqs/show', [
            'faq' => $faq,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Faq $faq)
    {
        return Inertia::render('admin/faqs/edit', [
            'faq' => $faq,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FaqUpdateRequest $request, Faq $faq)
    {
        $validated = $request->validated();

        $faq->update($validated);

        return to_route('admin.faqs.index')->with('success', 'FAQ updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Faq $faq)
    {
        $faq->delete();

        return to_route('admin.faqs.index')->with('success', 'FAQ deleted successfully.');
    }
}
