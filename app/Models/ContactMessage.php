<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    protected $fillable = [
        'thread_id',
        'sender_id',
        'body',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ContactThread::class, 'thread_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Drives "show an inline thumbnail" vs "show a file chip" in every
     * message-formatting spot (web formatMessage() ×2, ContactMessageResource)
     * so that decision lives in one place.
     */
    public function isImageAttachment(): bool
    {
        return $this->attachment_mime !== null && str_starts_with($this->attachment_mime, 'image/');
    }
}
