<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Mail\AdminAnnouncementMail;
use Illuminate\Support\Facades\Mail;

class AnnouncementController extends Controller
{
    /**
     * Display the announcement compose view.
     */
    public function index()
    {
        return view('admin.announcements.index');
    }

    /**
     * Dispatch the custom announcement to all registered users.
     */
    public function send(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'content' => 'required|string|min:10',
        ]);

        $users = User::whereNotNull('email')->get();

        if ($users->isEmpty()) {
            return back()->with('error', 'No registered users found to send the announcement to.');
        }

        $sentCount = 0;
        foreach ($users as $user) {
            try {
                Mail::to($user->email)->send(new AdminAnnouncementMail($request->subject, $request->content, $user));
                $sentCount++;
            } catch (\Exception $e) {
                // Keep sending to other users even if one fails
                \Illuminate\Support\Facades\Log::error("Failed to send announcement email to {$user->email}: " . $e->getMessage());
            }
        }

        return back()->with('success', "Announcement has been successfully sent to {$sentCount} users.");
    }
}
