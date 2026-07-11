class AppNotification {
  AppNotification({
    required this.id,
    this.icon,
    required this.title,
    required this.body,
    this.url,
    required this.read,
    required this.createdAt,
  });

  final String id;
  final String? icon;
  final String title;
  final String body;
  final String? url;
  final bool read;
  final DateTime createdAt;

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    return AppNotification(
      id: json['id'] as String,
      icon: json['icon'] as String?,
      title: json['title'] as String,
      body: json['body'] as String,
      url: json['url'] as String?,
      read: json['read'] as bool,
      createdAt: DateTime.parse(json['created_at'] as String),
    );
  }
}
