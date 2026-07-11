<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'platform' => ['sometimes', 'string', 'in:android,ios'],
        ]);

        // Looked up globally by token (not scoped to the current user) so a
        // device previously registered to a different account — e.g. a
        // shared/borrowed phone — gets reassigned instead of hitting the
        // unique constraint on `token`.
        DeviceToken::updateOrCreate(
            ['token' => $validated['token']],
            ['user_id' => $request->user()->id, 'platform' => $validated['platform'] ?? 'android'],
        );

        return response()->json(['message' => __('บันทึกอุปกรณ์สำเร็จ')]);
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate(['token' => ['required', 'string']]);

        $request->user()->deviceTokens()->where('token', $validated['token'])->delete();

        return response()->json(['message' => __('ลบอุปกรณ์สำเร็จ')]);
    }
}
