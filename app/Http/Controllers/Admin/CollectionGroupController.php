<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CollectionGroupCreateRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lunar\Models\CollectionGroup;

class CollectionGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $groups = CollectionGroup::query()
            ->withCount('collections')
            ->get();

        return Inertia::render('admin/collection-groups/index', [
            'groups' => $groups,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CollectionGroupCreateRequest $request)
    {
        $validated = $request->validated();

        CollectionGroup::create($validated);

        return to_route('admin.collection-groups.index')->with('success', 'Collection group created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CollectionGroup $collectionGroup)
    {
        return Inertia::render('admin/collection-groups/edit', [
            'group' => $collectionGroup->load('collections'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
