<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KnowledgeCreateRequest;
use App\Http\Requests\Admin\KnowledgeUpdateRequest;
use App\Models\Knowledge;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KnowledgeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $knowledge = Knowledge::all();

        return Inertia::render('admin/knowledge/index', [
            'knowledge' => $knowledge,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admin/knowledge/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(KnowledgeCreateRequest $request)
    {
        $validated = $request->validated();

        $validated['published_at'] = isset($validated['published_date']) && isset($validated['published_time']) ? Carbon::createFromFormat('d-m-Y H:i:s', $validated['published_date'] . ' ' . $validated['published_time']) : null;

        if ($validated['published_at'] && $validated['published_at']->isFuture()) {
            $validated['is_published'] = false;
        } else {
            $validated['is_published'] = true;
        }

        Knowledge::create($validated);

        return to_route('admin.knowledge.index')->with('success', 'Knowledge entry created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Knowledge $knowledge)
    {
        return Inertia::render('admin/knowledge/show', [
            'knowledge' => $knowledge,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Knowledge $knowledge)
    {
        $knowledge->published_date = $knowledge->published_at ? $knowledge->published_at->format('d-m-Y') : null;
        $knowledge->published_time = $knowledge->published_at ? $knowledge->published_at->format('H:i:s') : null;
        
        return Inertia::render('admin/knowledge/edit', [
            'knowledge' => $knowledge,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(KnowledgeUpdateRequest $request, Knowledge $knowledge)
    {
        $validated = $request->validated();

        $validated['published_at'] = isset($validated['published_date']) && isset($validated['published_time']) ? Carbon::createFromFormat('d-m-Y H:i:s', $validated['published_date'] . ' ' . $validated['published_time']) : null;

        if ($validated['published_at'] && $validated['published_at']->isFuture()) {
            $validated['is_published'] = false;
        } else {
            $validated['is_published'] = true;
        }

        $knowledge->update($validated);

        return to_route('admin.knowledge.index')->with('success', 'Knowledge entry updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Knowledge $knowledge)
    {
        $knowledge->delete();

        return to_route('admin.knowledge.index')->with('success', 'Knowledge entry deleted successfully.');
    }
}
