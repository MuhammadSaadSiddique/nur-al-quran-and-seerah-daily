<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AppNotification;
use App\Models\DeviceToken;
use App\Mail\AdminAnnouncementMail;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AnnouncementController extends Controller
{
    /**
     * Display the announcement compose view with history.
     */
    public function index()
    {
        $recentAnnouncements = AppNotification::with('sender')->latest()->paginate(10);
        $activeDevicesCount = DeviceToken::where('is_active', true)->count();
        $usersCount = User::whereNotNull('email')->count();

        return view('admin.announcements.index', compact('recentAnnouncements', 'activeDevicesCount', 'usersCount'));
    }

    /**
     * Dispatch announcement to mobile app and/or emails.
     */
    public function send(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'content' => 'required|string|min:5',
        ]);

        $sendPush = $request->has('send_push') ? $request->boolean('send_push') : true;
        $sendEmail = $request->has('send_email') ? $request->boolean('send_email') : false;

        if (!$sendPush && !$sendEmail) {
            return back()->with('error', 'Please select at least one delivery channel (Mobile App Push or Email).')->withInput();
        }

        // 1. Create Notification Record
        $notification = AppNotification::create([
            'title' => $request->subject,
            'message' => $request->content,
            'type' => 'announcement',
            'sent_by' => auth()->id(),
            'send_push' => $sendPush,
            'send_email' => $sendEmail,
        ]);

        $messages = [];

        // 2. Dispatch Push Notification to Mobile App
        if ($sendPush) {
            $devicesCount = PushNotificationService::broadcast($notification);
            $messages[] = "Broadcasted to mobile app (reachable by {$devicesCount} registered device(s) & in-app sync)";
        }

        // 3. Dispatch Email to Users
        if ($sendEmail) {
            $users = User::whereNotNull('email')->get();
            $sentEmailCount = 0;

            foreach ($users as $user) {
                try {
                    Mail::to($user->email)->send(new AdminAnnouncementMail($request->subject, $request->content, $user));
                    $sentEmailCount++;
                } catch (\Exception $e) {
                    Log::error("Failed to send announcement email to {$user->email}: " . $e->getMessage());
                }
            }

            $messages[] = "Emailed to {$sentEmailCount} user inbox(es)";
        }

        $summary = implode(' and ', $messages);
        return back()->with('success', "✅ Announcement published successfully! {$summary}.");
    }
}
