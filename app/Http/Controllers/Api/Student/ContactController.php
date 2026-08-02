<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContactMessageResource;
use App\Http\Resources\ContactThreadResource;
use App\Models\ContactThread;
use App\Models\User;
use App\Notifications\ContactMessageReplied;
use App\Notifications\ContactThreadStarted;
use App\Services\SafeNotifier;
use App\Services\StudentActivityFeed;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Flagged/rejected items the student could start a thread about — the
     * mobile equivalent of the web create form's topic picker. Same source
     * as Student\ContactController::create(), see
     * StudentActivityFeed::askableTopics().
     */
    public function topics(Request $request, StudentActivityFeed $feed)
    {
        $topics = $feed->askableTopics($request->user())
            ->map(fn ($topic) => [
                'title' => $topic->title,
                'type' => $topic->type,
                'activity_id' => $topic->activity_id,
                'reason' => $topic->reason,
            ]);

        return response()->json(['data' => $topics]);
    }

    /**
     * Static office-contact details for the app's contact screen — same
     * config('services.srru.*') values the web contact page reads (see
     * partials/contact-info-panel.blade.php), so both platforms always
     * show identical info with nothing hand-duplicated.
     */
    public function officeInfo()
    {
        return response()->json([
            'data' => [
                'phone' => config('services.srru.office_phone'),
                'email' => config('services.srru.office_email'),
                'address' => config('services.srru.office_address'),
                'hours' => config('services.srru.office_hours'),
                'response_time' => config('services.srru.response_time'),
            ],
        ]);
    }

    public function index(Request $request)
    {
        $threads = ContactThread::where('user_id', $request->user()->id)
            ->with('assignedAdmin')
            ->latest('last_message_at')
            ->paginate(15);

        return response()->json([
            'data' => ContactThreadResource::collection($threads->items()),
            'meta' => [
                'current_page' => $threads->currentPage(),
                'last_page' => $threads->lastPage(),
                'per_page' => $threads->perPage(),
                'total' => $threads->total(),
            ],
        ]);
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

        return response()->json([
            'message' => __('ส่งข้อความสำเร็จ รอเจ้าหน้าที่ตอบกลับ'),
            'thread' => new ContactThreadResource($thread),
        ]);
    }

    public function show(Request $request, ContactThread $contactThread)
    {
        abort_unless($contactThread->user_id === $request->user()->id, 403);

        $contactThread->update(['student_unread' => false]);
        $contactThread->load('messages.sender', 'assignedAdmin');

        return response()->json([
            'thread' => new ContactThreadResource($contactThread),
            'messages' => ContactMessageResource::collection($contactThread->messages),
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

        $contactThread->update([
            'status' => 'open',
            'admin_unread' => true,
            'last_message_at' => now(),
        ]);

        $admins = User::whereIn('role', ['admin', 'super_admin'])->get();
        SafeNotifier::send($admins, new ContactMessageReplied($message->load('sender', 'thread')));

        return response()->json([
            'message' => __('ส่งข้อความสำเร็จ'),
            'data' => new ContactMessageResource($message),
        ]);
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
}
