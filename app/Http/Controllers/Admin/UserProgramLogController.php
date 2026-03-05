<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgramLog;
use App\Models\User;
use Inertia\Inertia;

class UserProgramLogController extends Controller
{
    /**
     * Display a listing of the user's program logs.
     */
    public function index(User $user)
    {
        $programLogs = ProgramLog::where('user_id', $user->id)
            ->with('therapy')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('admin/users/program-log/index', [
            'user' => $user,
            'programLogs' => $programLogs,
        ]);
    }
}
