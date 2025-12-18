<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MusicResource;
use App\Models\Music;

class MusicController extends Controller
{
    /**
     * Display a listing of active music.
     */
    public function index()
    {
        $music = Music::where('is_active', true)
            ->orderBy('title')
            ->get();

        return MusicResource::collection($music)
            ->additional([
                'status' => 200,
                'message' => 'Music retrieved successfully.',
            ]);
    }
}
