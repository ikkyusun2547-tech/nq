import '../../core/api_client.dart';
import '../../core/models/dashboard.dart';

class DashboardData {
  DashboardData({
    required this.summary,
    required this.approved,
    required this.pending,
    required this.rejected,
    required this.hasMoreApproved,
    required this.hasMorePending,
    required this.hasMoreRejected,
  });

  final DashboardSummary summary;
  final List<DashboardFeedItem> approved;
  final List<DashboardFeedItem> pending;
  final List<DashboardFeedItem> rejected;

  /// Set when a bucket has more than the 5 rows shown here — see
  /// Api\Student\DashboardController's has_more_* keys.
  final bool hasMoreApproved;
  final bool hasMorePending;
  final bool hasMoreRejected;
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
      hasMoreApproved: data['has_more_approved'] as bool? ?? false,
      hasMorePending: data['has_more_pending'] as bool? ?? false,
      hasMoreRejected: data['has_more_rejected'] as bool? ?? false,
    );
  }
}
