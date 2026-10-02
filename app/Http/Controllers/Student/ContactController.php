<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\ContactThread;
use App\Models\User;
use App\Notifications\ContactMessageReplied;
use App\Notifications\ContactThreadStarted;
use App\Services\SafeNotifier;
use App\Services\StudentActivityFeed;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $threads = ContactThread::where('user_id', $request->user()->id)
            ->latest('last_message_at')
            ->paginate(15);

        return view('student.contact.index', compact('threads'));
    }

    /**
     * Prefilled from wherever the student clicked "ติดต่อเจ้าหน้าที่" — a
     * flagged check-in or rejected request passes ?subject=&context_type=
     * &context_id= so the new thread already knows what it's about. When
     * arriving fresh (no query string), $topics lets the student pick one
     * of their own flagged/rejected items from a dropdown instead of
     * free-typing a subject.
     */
    public function create(Request $request, StudentActivityFeed $feed)
    {
        $topics = $feed->askableTopics($request->user());

        return view('student.contact.create', compact('topics'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'context_type' => ['nullable', 'string', 'max:50'],
            'context_id' => ['nullable', 'integer'],
            ...self::messageRules(),
        ]);

        $thread = ContactThread::create([
            'user_id' => $request->user()->id,
            'subject' => $validated['subject'],
            'context_type' => $validated['context_type'] ?? null,
            'context_id' => $validated['context_id'] ?? null,
            'last_message_at' => now(),
            'admin_unread' => true,
        ]);

        $thread->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $validated['body'] ?? null,
            ...$this->attachmentFields($request),
        ]);

        $admins = User::whereIn('role', ['admin', 'super_admin'])->get();
        SafeNotifier::send($admins, new ContactThreadStarted($thread->load('student')));

        return redirect()
            ->route('contact.show', $thread)
            ->with('status', __('ส่งข้อความสำเร็จ รอเจ้าหน้าที่ตอบกลับ'));
    }

    public function show(Request $request, ContactThread $contactThread)
    {
        abort_unless($contactThread->user_id === $request->user()->id, 403);

        $contactThread->update(['student_unread' => false]);
        $contactThread->load('messages.sender', 'assignedAdmin');

        return view('student.contact.show', ['thread' => $contactThread, 'fullscreenChat' => true]);
    }

    /**
     * Polled every few seconds by the thread show page so replies show up
     * without a manual reload — see notification-bell.blade.php's poll()
     * for the same shape of pattern. Thread message counts stay small, so
     * this just refetches everything rather than tracking a since-id.
     */
    public function poll(Request $request, ContactThread $contactThread)
    {
        abort_unless($contactThread->user_id === $request->user()->id, 403);

        // ?seen=1: an open chat window (resources/js/chat-dock.js) is showing it.
        if ($request->boolean('seen') && $contactThread->student_unread) {
            $contactThread->update(['student_unread' => false]);
        }

        $contactThread->loadMissing('assignedAdmin');

        return response()->json([
            'status' => $contactThread->status,
            'assigned_admin_name' => $contactThread->assignedAdmin?->name_thai ?? $contactThread->assignedAdmin?->name,
            'messages' => $contactThread->messages()
                ->with('sender')
                ->get()
                ->map(fn (ContactMessage $message) => $this->formatMessage($message, $request->user()->id)),
        ]);
    }

    public function reply(Request $request, ContactThread $contactThread)
    {
        abort_unless($contactThread->user_id === $request->user()->id, 403);

        $validated = $request->validate(self::messageRules());

        $message = $contactThread->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $validated['body'] ?? null,
            ...$this->attachmentFields($request),
        ]);

        // Replying to a closed thread reopens it — the student clearly
        // still has something to say, no reason to make them find a
        // separate "reopen" action.
        $contactThread->update([
            'status' => 'open',
            'admin_unread' => true,
            'last_message_at' => now(),
        ]);

        $admins = User::whereIn('role', ['admin', 'super_admin'])->get();
        SafeNotifier::send($admins, new ContactMessageReplied($message->load('sender', 'thread')));

        if ($request->wantsJson()) {
            return response()->json(['data' => $this->formatMessage($message, $request->user()->id)]);
        }

        return redirect()->route('contact.show', $contactThread)->with('status', __('ส่งข้อความสำเร็จ'));
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
            // Raw epoch alongside the display string above — the blade view
            // uses this to decide when consecutive messages from the same
            // sender are close enough together to collapse into one visual
            // group (avatar/timestamp shown once, not per message).
            'created_at_ts' => $message->created_at->timestamp,
            'attachment_url' => $message->attachment_path ? asset('storage/'.$message->attachment_path) : null,
            'attachment_name' => $message->attachment_name,
            'is_image_attachment' => $message->isImageAttachment(),
        ];
    }

    /**
     * Shared by store()/reply() (here and in Admin\ContactController) — a
     * message needs a body, an attachment, or both, but not neither.
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
}
