/**
 * Facebook-style minimised chats (desktop only). Pressing "ย่อแชท" on a
 * conversation page stores it here and returns to the previous page; the
 * conversation then lives in a small window docked bottom-right on every
 * page until it is closed. State is per browser and per signed-in user
 * (localStorage), so a different account on the same machine never sees it.
 *
 * Entries: { id, title, name, avatar, showUrl, pollUrl, replyUrl,
 *            collapsed, lastSeenId }
 */
const MAX_WINDOWS = 3;

function storageKey(userId) {
    return `srru.chatDock.${userId}`;
}

function load(userId) {
    try {
        const raw = JSON.parse(localStorage.getItem(storageKey(userId)) || '[]');
        return Array.isArray(raw) ? raw : [];
    } catch (e) {
        return [];
    }
}

function save(userId, entries) {
    try {
        localStorage.setItem(storageKey(userId), JSON.stringify(entries.map(({ messages, status, sending, error, body, file, fileName, filePreview, fileIsImage, ...keep }) => keep)));
    } catch (e) {}
}

/** Called from a conversation page's "ย่อแชท" button. */
window.minimizeChat = function (userId, entry) {
    const entries = load(userId).filter((e) => e.showUrl !== entry.showUrl);
    entries.unshift({ ...entry, collapsed: false, lastSeenId: entry.lastSeenId ?? 0 });
    save(userId, entries.slice(0, MAX_WINDOWS));

    const back = document.referrer && new URL(document.referrer).origin === location.origin && new URL(document.referrer).pathname !== location.pathname;
    if (back) {
        history.back();
    } else {
        location.href = entry.indexUrl || '/';
    }
};

document.addEventListener('alpine:init', () => {
    window.Alpine.data('chatDock', (config) => ({
        userId: config.userId,
        labels: config.labels,
        windows: [],
        timer: null,

        init() {
            // The conversation already open full-page doesn't also need a window.
            this.windows = load(this.userId)
                .filter((e) => new URL(e.showUrl, location.origin).pathname !== location.pathname)
                .map((e) => ({ ...e, messages: [], status: 'open', sending: false, error: '', body: '', file: null, fileName: '', filePreview: null, fileIsImage: false }));

            this.windows.forEach((w) => this.poll(w));
            this.timer = setInterval(() => this.tick(), 5000);
            window.addEventListener('push-notification-received', () => this.windows.forEach((w) => this.poll(w)));
            document.addEventListener('visibilitychange', () => {
                if (! document.hidden) this.windows.forEach((w) => this.poll(w));
            });
        },

        tick() {
            if (document.hidden) return;
            this.ticks = (this.ticks || 0) + 1;
            // Open windows every 5s, collapsed ones every 15s (only for the badge).
            this.windows.forEach((w) => {
                if (! w.collapsed || this.ticks % 3 === 0) this.poll(w);
            });
        },

        persist() {
            save(this.userId, this.windows);
        },

        async poll(w) {
            try {
                const url = new URL(w.pollUrl, location.origin);
                if (! w.collapsed && ! document.hidden) url.searchParams.set('seen', '1');
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                if (res.status === 403 || res.status === 404) {
                    this.close(w);
                    return;
                }
                if (! res.ok) return;
                const data = await res.json();
                const grew = data.messages.length !== w.messages.length;
                w.status = data.status;
                w.messages = data.messages;
                if (! w.collapsed) {
                    this.markSeen(w);
                    if (grew) this.scrollDown(w);
                }
            } catch (e) {}
        },

        unread(w) {
            return w.messages.filter((m) => ! m.is_mine && m.id > (w.lastSeenId || 0)).length;
        },

        markSeen(w) {
            const last = w.messages.length ? w.messages[w.messages.length - 1].id : 0;
            if (last !== w.lastSeenId) {
                w.lastSeenId = last;
                this.persist();
            }
        },

        toggle(w) {
            w.collapsed = ! w.collapsed;
            if (! w.collapsed) {
                this.markSeen(w);
                this.scrollDown(w);
                this.poll(w);
            }
            this.persist();
        },

        close(w) {
            this.windows = this.windows.filter((x) => x !== w);
            this.persist();
        },

        scrollDown(w) {
            this.$nextTick(() => {
                const box = document.getElementById(`chat-dock-${w.id}`);
                if (box) box.scrollTop = box.scrollHeight;
            });
        },

        timeOf(m) {
            return (m.created_at || '').split(' ')[1] || '';
        },

        attach(w, file) {
            if (! file) return;
            this.clearFile(w);
            w.file = file;
            w.fileName = file.name || 'image.png';
            w.fileIsImage = (file.type || '').startsWith('image/');
            w.filePreview = w.fileIsImage ? URL.createObjectURL(file) : null;
            w.error = file.size > 5 * 1024 * 1024 ? this.labels.fileRejected : '';
        },

        clearFile(w) {
            if (w.filePreview) URL.revokeObjectURL(w.filePreview);
            w.file = null;
            w.fileName = '';
            w.filePreview = null;
            w.fileIsImage = false;
            const input = document.getElementById(`chat-dock-file-${w.id}`);
            if (input) input.value = '';
        },

        // A screenshot pasted into the message box is attached, like Messenger.
        onPaste(w, event) {
            const item = [...(event.clipboardData?.items || [])].find((i) => i.kind === 'file');
            if (! item) return;
            event.preventDefault();
            this.attach(w, item.getAsFile());
        },

        canSend(w) {
            return ! w.sending && (w.body.trim() !== '' || !! w.file);
        },

        async send(w) {
            const body = w.body.trim();
            if (! this.canSend(w)) return;
            w.sending = true;
            w.error = '';
            try {
                const form = new FormData();
                if (body) form.append('body', body);
                if (w.file) form.append('attachment', w.file, w.fileName);
                const res = await fetch(w.replyUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: form,
                });
                if (res.ok) {
                    const data = await res.json();
                    w.messages.push(data.data);
                    w.body = '';
                    this.clearFile(w);
                    this.markSeen(w);
                    this.scrollDown(w);
                } else if (res.status === 422) {
                    // Too big or an unsupported type (see messageRules()).
                    w.error = this.labels.fileRejected;
                } else {
                    w.error = this.labels.sendFailed;
                }
            } catch (e) {
                w.error = this.labels.sendFailed;
            }
            w.sending = false;
        },
    }));
});
