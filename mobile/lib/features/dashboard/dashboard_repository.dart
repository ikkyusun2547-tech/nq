import '../../core/api_client.dart';
import '../../core/models/dashboard.dart';

class DashboardData {
  DashboardData({required this.summary, required this.approved, required this.pending, required this.rejected});

  final DashboardSummary summary;
  final List<DashboardFeedItem> approved;
  final List<DashboardFeedItem> pending;
  final List<DashboardFeedItem> rejected;
}

class DashboardRepository {
  DashboardRepository({required this.apiClient});

  final ApiClient apiClient;

  Future<DashboardData> fetch() async {
    final response = await apiClient.dio.get('/dashboard');
    final data = response.data as Map<String, dynamic>;

    List<DashboardFeedItem> parseList(String key) {
      return (data[key] as List<dynamic>)
          .map((e) => DashboardFeedItem.fromJson(e as Map<String, dynamic>))
          .toList();
    }

    return DashboardData(
      summary: DashboardSummary.fromJson(data['summary'] as Map<String, dynamic>),
      approved: parseList('approved'),
      pending: parseList('pending'),
      rejected: parseList('rejected'),
    );
  }
}
