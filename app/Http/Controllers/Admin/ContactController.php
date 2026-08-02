<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\ContactThread;
use App\Notifications\ContactMessageReplied;
use App\Services\SafeNotifier;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'open');

        $threads = ContactThread::with(['student', 'assignedAdmin'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->whereHas('student', function ($userQuery) use ($search) {
                    $userQuery->where('name_thai', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('student_id', 'like', "%{$search}%");
                });
            })
            ->latest('last_message_at')
            ->paginate(20)
            ->withQueryString();

        // Tab-pill counts — independent of the search box so they always
        // reflect the true size of each bucket, not just the current view.
        $tabCounts = ContactThread::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $tabCounts['all'] = $tabCounts->sum();

        return view('admin.contact.index', compact('threads', 'status', 'tabCounts'));
    }

    public function show(ContactThread $contactThread)
    {
        $contactThread->update(['admin_unread' => false]);
        $contactThread->load(['messages.sender', 'student', 'assignedAdmin']);

        return view('admin.contact.show', ['thread' => $contactThread, 'fullscreenChat' => true]);
    }

    /**
     * Polled every few seconds by the thread show page — see
     * Student\ContactController::poll() for the shared shape/reasoning.
     */
    public function poll(Request $request, ContactThread $contactThread)
    {
        return response()->json([
            'status' => $contactThread->status,
            'messages' => $contactThread->messages()
                ->with('sender')
                ->get()
                ->map(fn (ContactMessage $message) => $this->formatMessage($message, $request->user()->id)),
        ]);
    }

    public function reply(Request $request, ContactThread $contactThread)
    {
        $validated = $request->validate(self::messageRules());

        $message = $contactThread->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $validated['body'] ?? null,
            ...$this->attachmentFields($request),
        ]);

        $contactThread->update([
            // Replying claims the thread if nobody has yet — the same signal
            // a shared inbox uses elsewhere: the first admin to act on it is
            // shown as the one handling it, without blocking anyone else.
            'assigned_admin_id' => $contactThread->assigned_admin_id ?? $request->user()->id,
            'student_unread' => true,
            'admin_unread' => false,
            'last_message_at' => now(),
        ]);

        SafeNotifier::send($contactThread->student, new ContactMessageReplied($message->load('sender', 'thread')));

        if ($request->wantsJson()) {
            return response()->json(['data' => $this->formatMessage($message, $request->user()->id)]);
        }

        return back()->with('status', __('ส่งข้อความสำเร็จ'));
    }

    private function formatMessage(ContactMessage $message, int $viewerId): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'sender_name' => $message->sender->name_thai ?? $message->sender->name,
            'sender_avatar' => $message->sender->avatar_url,
            'is_mine' => $message->sender_id === $viewerId,
            'created_at' => $message->created_at->format('d/m/Y H:i'),
            // See Student\ContactController::formatMessage() — same
            // grouping-threshold reasoning, shared blade template pattern.
            'created_at_ts' => $message->created_at->timestamp,
            'attachment_url' => $message->attachment_path ? asset('storage/'.$message->attachment_path) : null,
            'attachment_name' => $message->attachment_name,
            'is_image_attachment' => $message->isImageAttachment(),
        ];
    }

    /**
     * See Student\ContactController::messageRules() — a message needs a
     * body, an attachment, or both, but not neither.
     */
    private static function messageRules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:2000', 'required_without:attachment'],
            'attachment' => [
                'nullable', 'file', 'max:5120',
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt',
                'required_without:body',
            ],
        ];
    }

    private function attachmentFields(Request $request): array
    {
        if (! $request->hasFile('attachment')) {
            return [];
        }

        $file = $request->file('attachment');

        return [
            'attachment_path' => $file->store('contact-attachments', 'public'),
            'attachment_name' => $file->getClientOriginalName(),
            'attachment_mime' => $file->getMimeType(),
        ];
    }

    public function claim(Request $request, ContactThread $contactThread)
    {
        $contactThread->update(['assigned_admin_id' => $request->user()->id]);

        return back()->with('status', __('รับเรื่องนี้แล้ว'));
    }

    public function release(ContactThread $contactThread)
    {
        $contactThread->update(['assigned_admin_id' => null]);

        return back()->with('status', __('ปล่อยเรื่องนี้แล้ว'));
    }

    public function close(ContactThread $contactThread)
    {
        $contactThread->update(['status' => 'closed']);

        return back()->with('status', __('ปิดเรื่องสำเร็จ'));
    }

    public function reopen(ContactThread $contactThread)
    {
        $contactThread->update(['status' => 'open']);

        return back()->with('status', __('เปิดเรื่องกลับสำเร็จ'));
    }
}
