<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function toggleAdmin(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot remove your own admin privileges.');
        }

        $user->is_admin = !$user->is_admin;
        $user->save();

        $status = $user->is_admin ? 'granted admin privileges.' : 'removed from admin role.';
        return back()->with('success', "User {$user->email} has been {$status}");
    }

    public function toggleResearcher(User $user)
    {
        $user->is_researcher = !$user->is_researcher;
        $user->save();

        $status = $user->is_researcher ? 'granted researcher privileges.' : 'removed from researcher role.';
        return back()->with('success', "User {$user->email} has been {$status}");
    }
}
