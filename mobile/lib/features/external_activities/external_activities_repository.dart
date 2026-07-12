import 'dart:io';

import 'package:dio/dio.dart';

import '../../core/api_client.dart';
import '../../core/models/external_activity_request.dart';

class ExternalActivitiesData {
  ExternalActivitiesData({
    required this.hoursRemaining,
    required this.requests,
  });

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
        // Preserves the real extension (.jpg/.png/.pdf) rather than a
        // hardcoded 'proof.jpg' — Laravel's Storage::put keeps whatever
        // extension the upload arrives with, so a PDF sent as "proof.jpg"
        // would get stored (and later served) with the wrong one.
        'proof_image': await MultipartFile.fromFile(
          proofImagePath,
          filename: proofImagePath.split(Platform.pathSeparator).last,
        ),
      }),
    );
  }

  /// Only valid while the request is still 'pending' — the backend rejects
  /// (422) any attempt to cancel one an admin has already reviewed.
  Future<void> cancel(int id) async {
    await apiClient.dio.delete('/external-activities/$id');
  }
}
