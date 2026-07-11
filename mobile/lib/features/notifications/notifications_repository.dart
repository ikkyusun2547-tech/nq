import '../../core/api_client.dart';
import '../../core/models/app_notification.dart';

class NotificationsPage {
  NotificationsPage({
    required this.notifications,
    required this.unreadCount,
    required this.currentPage,
    required this.lastPage,
  });

  final List<AppNotification> notifications;
  final int unreadCount;
  final int currentPage;
  final int lastPage;
}

class NotificationsRepository {
  NotificationsRepository({required this.apiClient});

  final ApiClient apiClient;

  Future<NotificationsPage> fetch({int page = 1}) async {
    final response = await apiClient.dio.get(
      '/notifications',
      queryParameters: {'page': page},
    );
    final data = response.data as Map<String, dynamic>;
    final meta = data['meta'] as Map<String, dynamic>;

    return NotificationsPage(
      notifications: (data['data'] as List<dynamic>)
          .map((n) => AppNotification.fromJson(n as Map<String, dynamic>))
          .toList(),
      unreadCount: data['unread_count'] as int,
      currentPage: meta['current_page'] as int,
      lastPage: meta['last_page'] as int,
    );
  }

  Future<int> fetchUnreadCount() async {
    final page = await fetch();
    return page.unreadCount;
  }

  Future<void> markRead(String id) => apiClient.dio.post('/notifications/$id/read');

  Future<void> markAllRead() => apiClient.dio.post('/notifications/read-all');
}
