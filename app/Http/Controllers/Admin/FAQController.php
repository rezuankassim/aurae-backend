<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FaqCreateRequest;
use App\Http\Requests\Admin\FaqUpdateRequest;
use App\Models\Faq;
use Inertia\Inertia;

class FAQController extends Controller
{
    public function index()
    {
        $faqs = Faq::all();

        return Inertia::render('admin/faqs/index', [
            'faqs' => $faqs,
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/faqs/create');
    }

    public function store(FaqCreateRequest $request)
    {
        $validated = $request->validated();

        Faq::create($validated);

        return to_route('admin.faqs.index')->with('success', 'FAQ created successfully.');
    }

    public function show(Faq $faq)
    {
        return Inertia::render('admin/faqs/show', [
            'faq' => $faq,
        ]);
    }

    public function edit(Faq $faq)
    {
        return Inertia::render('admin/faqs/edit', [
            'faq' => $faq,
        ]);
    }

    public function update(FaqUpdateRequest $request, Faq $faq)
    {
        $validated = $request->validated();

        $faq->update($validated);

        return to_route('admin.faqs.index')->with('success', 'FAQ updated successfully.');
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();

        return to_route('admin.faqs.index')->with('success', 'FAQ deleted successfully.');
    }
}
