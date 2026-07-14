import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'api_client.dart';
import 'auth_storage.dart';
import 'push_notifications.dart';
import '../features/auth/auth_repository.dart';
import '../features/profile_setup/profile_setup_repository.dart';
import '../features/dashboard/dashboard_repository.dart';
import '../features/activity_history/activity_history_repository.dart';
import '../features/activities/activities_repository.dart';
import '../features/checkin/checkin_repository.dart';
import '../features/self_checkin/self_checkin_repository.dart';
import '../features/late_checkin/late_checkin_repository.dart';
import '../features/external_activities/external_activities_repository.dart';
import '../features/credit_transfers/credit_transfers_repository.dart';
import '../features/transcript/transcript_repository.dart';
import '../features/notifications/notifications_repository.dart';

final authStorageProvider = Provider<AuthStorage>((ref) => AuthStorage());

final apiClientProvider = Provider<ApiClient>((ref) {
  return ApiClient(authStorage: ref.watch(authStorageProvider));
});

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepository(
    apiClient: ref.watch(apiClientProvider),
    authStorage: ref.watch(authStorageProvider),
  );
});

final deviceTokenRepositoryProvider = Provider<DeviceTokenRepository>((ref) {
  return DeviceTokenRepository(apiClient: ref.watch(apiClientProvider));
});

final pushNotificationServiceProvider = Provider<PushNotificationService>((ref) {
  return PushNotificationService(
    deviceTokenRepository: ref.watch(deviceTokenRepositoryProvider),
    ref: ref,
  );
});

/// Which HomeShell bottom-nav tab is showing. Deliberately not autoDispose —
/// a tapped push notification (see push_notifications.dart) can set this
/// before HomeShell has ever been built (cold start), and the value must
/// survive until it mounts and reads it.
class HomeTabIndexNotifier extends Notifier<int> {
  @override
  int build() => 0;

  void set(int value) => state = value;
}

final homeTabIndexProvider = NotifierProvider<HomeTabIndexNotifier, int>(
  HomeTabIndexNotifier.new,
);

final profileSetupRepositoryProvider = Provider<ProfileSetupRepository>((ref) {
  return ProfileSetupRepository(apiClient: ref.watch(apiClientProvider));
});

final dashboardRepositoryProvider = Provider<DashboardRepository>((ref) {
  return DashboardRepository(apiClient: ref.watch(apiClientProvider));
});

final dashboardDataProvider = FutureProvider.autoDispose((ref) {
  return ref.watch(dashboardRepositoryProvider).fetch();
});

final activityHistoryRepositoryProvider = Provider<ActivityHistoryRepository>((ref) {
  return ActivityHistoryRepository(apiClient: ref.watch(apiClientProvider));
});

final activitiesRepositoryProvider = Provider<ActivitiesRepository>((ref) {
  return ActivitiesRepository(apiClient: ref.watch(apiClientProvider));
});

class StatusGroupNotifier extends Notifier<String> {
  @override
  String build() => 'open';

  void set(String value) => state = value;
}

final activitiesStatusGroupProvider = NotifierProvider.autoDispose<StatusGroupNotifier, String>(
  StatusGroupNotifier.new,
);

/// null = ทั้งหมด (no filter, matches any activity_level).
class ActivityLevelNotifier extends Notifier<String?> {
  @override
  String? build() => null;

  void set(String? value) => state = value;
}

final activitiesLevelFilterProvider = NotifierProvider.autoDispose<ActivityLevelNotifier, String?>(
  ActivityLevelNotifier.new,
);

/// null = ทุกหมวดหมู่ (no filter, matches any activity_category).
class ActivityCategoryNotifier extends Notifier<String?> {
  @override
  String? build() => null;

  void set(String? value) => state = value;
}

final activitiesCategoryFilterProvider = NotifierProvider.autoDispose<ActivityCategoryNotifier, String?>(
  ActivityCategoryNotifier.new,
);

/// Empty = no text filter. Set on search-field submit, not per-keystroke, to
/// match the web's submit-on-Enter behavior and avoid a request per letter.
class ActivitySearchNotifier extends Notifier<String> {
  @override
  String build() => '';

  void set(String value) => state = value;
}

final activitiesSearchProvider = NotifierProvider.autoDispose<ActivitySearchNotifier, String>(
  ActivitySearchNotifier.new,
);

final activitiesPageProvider = FutureProvider.autoDispose((ref) {
  final statusGroup = ref.watch(activitiesStatusGroupProvider);
  final level = ref.watch(activitiesLevelFilterProvider);
  final category = ref.watch(activitiesCategoryFilterProvider);
  final search = ref.watch(activitiesSearchProvider);

  return ref.watch(activitiesRepositoryProvider).fetch(
    statusGroup: statusGroup,
    activityLevel: level,
    activityCategory: category,
    search: search.isEmpty ? null : search,
  );
});

final checkInRepositoryProvider = Provider<CheckInRepository>((ref) {
  return CheckInRepository(apiClient: ref.watch(apiClientProvider));
});

final selfCheckInRepositoryProvider = Provider<SelfCheckInRepository>((ref) {
  return SelfCheckInRepository(apiClient: ref.watch(apiClientProvider));
});

final lateCheckInRepositoryProvider = Provider<LateCheckInRepository>((ref) {
  return LateCheckInRepository(apiClient: ref.watch(apiClientProvider));
});

final externalActivitiesRepositoryProvider = Provider<ExternalActivitiesRepository>((ref) {
  return ExternalActivitiesRepository(apiClient: ref.watch(apiClientProvider));
});

final externalActivitiesDataProvider = FutureProvider.autoDispose((ref) {
  return ref.watch(externalActivitiesRepositoryProvider).fetch();
});

final creditTransfersRepositoryProvider = Provider<CreditTransfersRepository>((ref) {
  return CreditTransfersRepository(apiClient: ref.watch(apiClientProvider));
});

final creditTransferRequestsProvider = FutureProvider.autoDispose((ref) {
  return ref.watch(creditTransfersRepositoryProvider).fetch();
});

final creditTransferPositionsProvider = FutureProvider.autoDispose((ref) {
  return ref.watch(creditTransfersRepositoryProvider).fetchPositions();
});

final transcriptRepositoryProvider = Provider<TranscriptRepository>((ref) {
  return TranscriptRepository(apiClient: ref.watch(apiClientProvider));
});

final notificationsRepositoryProvider = Provider<NotificationsRepository>((ref) {
  return NotificationsRepository(apiClient: ref.watch(apiClientProvider));
});

final notificationsPageProvider = FutureProvider.autoDispose((ref) {
  return ref.watch(notificationsRepositoryProvider).fetch();
});

final unreadNotificationCountProvider = FutureProvider.autoDispose((ref) {
  return ref.watch(notificationsRepositoryProvider).fetchUnreadCount();
});
