import 'package:dio/dio.dart';

import '../../core/api_client.dart';
import '../../core/models/external_activity_request.dart';

class ExternalActivitiesData {
  ExternalActivitiesData({required this.hoursRemaining, required this.requests});

  final int hoursRemaining;
  final List<ExternalActivityRequest> requests;
}

class ExternalActivitiesRepository {
  ExternalActivitiesRepository({required this.apiClient});

  final ApiClient apiClient;

  Future<ExternalActivitiesData> fetch() async {
    final response = await apiClient.dio.get('/external-activities');
    final body = response.data as Map<String, dynamic>;
    final items = (body['data'] as List).cast<Map<String, dynamic>>();

    return ExternalActivitiesData(
      hoursRemaining: body['hours_remaining'] as int,
      requests: items.map(ExternalActivityRequest.fromJson).toList(),
    );
  }

  Future<void> submit({
    required String title,
    required String organization,
    required String activityDate,
    required String activityCategory,
    required int hoursRequested,
    required String proofImagePath,
  }) async {
    await apiClient.dio.post(
      '/external-activities',
      data: FormData.fromMap({
        'title': title,
        'organization': organization,
        'activity_date': activityDate,
        'activity_category': activityCategory,
        'hours_requested': hoursRequested,
        'proof_image': await MultipartFile.fromFile(proofImagePath, filename: 'proof.jpg'),
      }),
    );
  }
}
