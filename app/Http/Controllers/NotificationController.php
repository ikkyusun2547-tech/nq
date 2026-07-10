<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function poll(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $user->notifications()->latest()->limit(8)->get()->map(fn ($n) => [
                'id' => $n->id,
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at->diffForHumans(),
                'icon' => $n->data['icon'] ?? 'check',
                'title' => __($n->data['title_key'] ?? ''),
                'body' => __($n->data['body_key'] ?? '', $n->data['body_params'] ?? []),
                'url' => $n->data['url'] ?? null,
            ]),
        ]);
    }

    public function read(Request $request, string $notification)
    {
        $notif = $request->user()->notifications()->findOrFail($notification);
        $notif->markAsRead();

        return redirect($this->relativePath($notif->data['url'] ?? null) ?? route('dashboard'));
    }

    /**
     * A notification's url is built with route()/url() at the moment it's
     * created and baked permanently into its stored data — including
     * whatever scheme+host that request happened to be on (localhost,
     * 127.0.0.1, an ngrok tunnel...). Redirecting to that absolute URL
     * verbatim can silently send the browser to a different origin than the
     * one it's actually authenticated on, which looks identical to a broken
     * link: the session cookie for the origin you're on never gets sent to
     * the other one, so the destination's own auth middleware bounces you to
     * its /login instead. Stripping back down to just the path keeps the
     * browser on the current origin no matter which origin created it.
     */
    private function relativePath(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $parts = parse_url($url);
        $path = $parts['path'] ?? '/';

        if (isset($parts['query'])) {
            $path .= '?'.$parts['query'];
        }

        return $path;
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }

    public function destroy(Request $request, string $notification)
    {
        $notif = $request->user()->notifications()->findOrFail($notification);
        $notif->delete();

        if ($request->wantsJson()) {
            return response()->json(['deleted' => true]);
        }

        return back()->with('status', __('ลบการแจ้งเตือนแล้ว'));
    }

    public function destroyAll(Request $request)
    {
        $request->user()->notifications()->delete();

        return back()->with('status', __('ลบการแจ้งเตือนทั้งหมดแล้ว'));
    }
}
