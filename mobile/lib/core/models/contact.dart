class ContactThread {
  ContactThread({
    required this.id,
    required this.subject,
    required this.status,
    this.contextType,
    this.contextId,
    this.lastMessageAt,
    required this.unread,
    required this.createdAt,
    this.assignedAdminName,
  });

  final int id;
  final String subject;
  final String status;
  final String? contextType;
  final int? contextId;
  final DateTime? lastMessageAt;
  final bool unread;
  final DateTime createdAt;

  /// Which staff member has claimed this thread, if any — same field the
  /// web contact chat shows (see Student\ContactController's
  /// contact/show.blade.php). Null until an admin claims it or replies.
  final String? assignedAdminName;

  factory ContactThread.fromJson(Map<String, dynamic> json) {
    return ContactThread(
      id: json['id'] as int,
      subject: json['subject'] as String,
      status: json['status'] as String,
      contextType: json['context_type'] as String?,
      contextId: json['context_id'] as int?,
      lastMessageAt: json['last_message_at'] != null
          ? DateTime.parse(json['last_message_at'] as String)
          : null,
      unread: json['unread'] as bool,
      createdAt: DateTime.parse(json['created_at'] as String),
      assignedAdminName: json['assigned_admin_name'] as String?,
    );
  }
}

class ContactMessage {
  ContactMessage({
    required this.id,
    this.body,
    required this.senderName,
    this.senderAvatar,
    required this.isMine,
    required this.createdAt,
    this.attachmentUrl,
    this.attachmentName,
    this.isImageAttachment = false,
  });

  final int id;

  /// Nullable — Facebook-style, a message can be just an attachment with
  /// no caption.
  final String? body;
  final String senderName;
  final String? senderAvatar;
  final bool isMine;
  final DateTime createdAt;
  final String? attachmentUrl;
  final String? attachmentName;
  final bool isImageAttachment;

  factory ContactMessage.fromJson(Map<String, dynamic> json) {
    return ContactMessage(
      id: json['id'] as int,
      body: json['body'] as String?,
      senderName: json['sender_name'] as String,
      senderAvatar: json['sender_avatar'] as String?,
      isMine: json['is_mine'] as bool,
      createdAt: DateTime.parse(json['created_at'] as String),
      attachmentUrl: json['attachment_url'] as String?,
      attachmentName: json['attachment_name'] as String?,
      isImageAttachment: json['is_image_attachment'] as bool? ?? false,
    );
  }
}

class ContactThreadDetail {
  ContactThreadDetail({required this.thread, required this.messages});

  final ContactThread thread;
  final List<ContactMessage> messages;
}

/// A flagged/rejected item the student could start a contact thread about
/// — shown as a picker on the create-thread screen when opened fresh
/// (not already deep-linked from a specific row), mirrors the web create
/// form's topic dropdown.
class ContactTopic {
  ContactTopic({
    required this.title,
    required this.type,
    this.activityId,
    this.reason,
  });

  final String title;
  final String type;
  final int? activityId;
  final String? reason;

  factory ContactTopic.fromJson(Map<String, dynamic> json) {
    return ContactTopic(
      title: json['title'] as String,
      type: json['type'] as String,
      activityId: json['activity_id'] as int?,
      reason: json['reason'] as String?,
    );
  }
}

/// Static office-contact details shown alongside the thread list — same
/// config('services.srru.*') values as the web contact page's info panel
/// (see partials/contact-info-panel.blade.php), fetched from GET /contact/info
/// so both platforms always show identical info. Each field is null until
/// the admin sets the corresponding env var (see .env.example).
class ContactInfo {
  ContactInfo({this.phone, this.email, this.address, this.hours, this.responseTime});

  final String? phone;
  final String? email;
  final String? address;
  final String? hours;
  final String? responseTime;

  factory ContactInfo.fromJson(Map<String, dynamic> json) {
    return ContactInfo(
      phone: json['phone'] as String?,
      email: json['email'] as String?,
      address: json['address'] as String?,
      hours: json['hours'] as String?,
      responseTime: json['response_time'] as String?,
    );
  }
}
