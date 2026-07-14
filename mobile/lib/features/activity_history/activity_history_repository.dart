import '../../core/api_client.dart';
import '../../core/models/dashboard.dart';

class ActivityHistoryPage {
  ActivityHistoryPage({
    required this.items,
    required this.currentPage,
    required this.lastPage,
  });

  final List<DashboardFeedItem> items;
  final int currentPage;
  final int lastPage;

  bool get hasNextPage => currentPage < lastPage;
}

/// Mobile counterpart of Student\ActivityHistoryController (web) — the
/// "ดูทั้งหมด" destination behind the dashboard's three preview lists.
class ActivityHistoryRepository {
  ActivityHistoryRepository({required this.apiClient});

  final ApiClient apiClient;

  Future<ActivityHistoryPage> fetch({required String status, int page = 1}) async {
    final response = await apiClient.dio.get(
      '/activity-history',
      queryParameters: {'status': status, 'page': page},
    );
    final data = response.data as Map<String, dynamic>;

    return ActivityHistoryPage(
      items: (data['items'] as List<dynamic>)
          .map((e) => DashboardFeedItem.fromJson(e as Map<String, dynamic>))
          .toList(),
      currentPage: data['current_page'] as int,
      lastPage: data['last_page'] as int,
    );
  }
}
