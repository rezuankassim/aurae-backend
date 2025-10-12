<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CollectionGroupCollectionCreateRequest;
use Illuminate\Http\Request;
use Lunar\Models\Collection;
use Lunar\Models\CollectionGroup;

class CollectionGroupCollectionController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(CollectionGroupCollectionCreateRequest $request, CollectionGroup $collectionGroup)
    {
        $validated = $request->validated();

        Collection::create([
            'attribute_data' => [
                'name' => new \Lunar\FieldTypes\TranslatedText(collect([
                    'en' => $validated['name'],
                ])),
            ],
            'collection_group_id' => $collectionGroup->id,
        ]);

        return to_route('admin.collection-groups.edit', $collectionGroup->id)->with('success', 'Collection created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
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
    public function destroy(CollectionGroup $collectionGroup, Collection $collection)
    {
        $collection->delete();

        return to_route('admin.collection-groups.edit', $collectionGroup->id)->with('success', 'Collection deleted successfully.');
    }
}
