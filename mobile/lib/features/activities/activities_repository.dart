import '../../core/api_client.dart';
import '../../core/models/activity.dart';

class ActivitiesPage {
  ActivitiesPage({
    required this.activities,
    required this.currentPage,
    required this.lastPage,
    required this.checkedInActivityIds,
    required this.lateCheckinStatuses,
  });

  final List<Activity> activities;
  final int currentPage;
  final int lastPage;
  final Set<int> checkedInActivityIds;
  final Map<String, String> lateCheckinStatuses;
}

class ActivitiesRepository {
  ActivitiesRepository({required this.apiClient});

  final ApiClient apiClient;

  Future<ActivitiesPage> fetch({
    String statusGroup = 'open',
    int page = 1,
    String? activityLevel,
    String? activityCategory,
    String? search,
  }) async {
    final response = await apiClient.dio.get(
      '/activities',
      queryParameters: {
        'status_group': statusGroup,
        'page': page,
        'activity_level': ?activityLevel,
        'activity_category': ?activityCategory,
        'search': ?search,
      },
    );
    final data = response.data as Map<String, dynamic>;
    final meta = data['meta'] as Map<String, dynamic>;

    return ActivitiesPage(
      activities: (data['data'] as List<dynamic>)
          .map((a) => Activity.fromJson(a as Map<String, dynamic>))
          .toList(),
      currentPage: meta['current_page'] as int,
      lastPage: meta['last_page'] as int,
      checkedInActivityIds: (data['checked_in_activity_ids'] as List<dynamic>).map((e) => e as int).toSet(),
      lateCheckinStatuses: _parseStringMap(data['late_checkin_statuses']),
    );
  }

  /// Laravel serializes an empty associative array (e.g.
  /// `pluck('status','activity_id')` with no rows) as a JSON `[]` rather
  /// than `{}`, since PHP can't distinguish an empty list from an empty map
  /// — so this must accept either shape.
  Map<String, String> _parseStringMap(dynamic value) {
    if (value is Map) {
      return value.map((k, v) => MapEntry(k.toString(), v as String));
    }

    return {};
  }
}
