<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactThread extends Model
{
    protected $fillable = [
        'user_id',
        'assigned_admin_id',
        'subject',
        'status',
        'context_type',
        'context_id',
        'last_message_at',
        'student_unread',
        'admin_unread',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'student_unread' => 'boolean',
            'admin_unread' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ContactMessage::class, 'thread_id')->orderBy('created_at');
    }
}
