<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserLoginActivityController extends Controller
{
     /**
     * Display a listing of the resource.
     */
    public function index(User $user)
    {
        $loginActivities = LoginActivity::where('user_id', $user->id)
            ->where('guard', '<>', 'staff')
            ->where('event', 'login')
            ->orderBy('occurred_at', 'desc')
            ->get();

        return Inertia::render('admin/users/login-activity/index', [
            'user' => $user,
            'loginActivities' => $loginActivities,
        ]);
    }
}
