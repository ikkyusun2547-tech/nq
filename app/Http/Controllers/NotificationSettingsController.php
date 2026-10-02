<?php

namespace App\Http\Controllers;

use App\Support\NotificationPreferences;
use Illuminate\Http\Request;

/** "ตั้งค่าการแจ้งเตือน" — the same page for students and admins (categories differ by role). */
class NotificationSettingsController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        return view('settings.notifications', [
            'categories' => NotificationPreferences::categoriesFor($user),
            'prefs' => $user->notification_preferences ?? [],
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $user->update(['notification_preferences' => NotificationPreferences::fromForm($user, $request->all())]);

        return back()->with('status', __('บันทึกการตั้งค่าการแจ้งเตือนแล้ว'));
    }
}
