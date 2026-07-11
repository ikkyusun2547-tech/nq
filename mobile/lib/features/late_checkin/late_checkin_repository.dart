import 'package:dio/dio.dart';

import '../../core/api_client.dart';

class LateCheckInRequestInfo {
  LateCheckInRequestInfo({required this.status, this.rejectReason});

  final String status;
  final String? rejectReason;
}

class LateCheckInRepository {
  LateCheckInRepository({required this.apiClient});

  final ApiClient apiClient;

  Future<LateCheckInRequestInfo?> fetchExisting(int activityId) async {
    final response = await apiClient.dio.get('/activities/$activityId/late-checkin');
    final existing = (response.data as Map<String, dynamic>)['existing_request'] as Map<String, dynamic>?;

    if (existing == null || existing.isEmpty) return null;

    return LateCheckInRequestInfo(
      status: existing['status'] as String,
      rejectReason: existing['reject_reason'] as String?,
    );
  }

  Future<String> submit({required int activityId, required String reason, required String proofImagePath}) async {
    final response = await apiClient.dio.post(
      '/activities/$activityId/late-checkin',
      data: FormData.fromMap({
        'reason': reason,
        'proof_image': await MultipartFile.fromFile(proofImagePath, filename: 'proof.jpg'),
      }),
    );

    return (response.data as Map<String, dynamic>)['message'] as String;
  }
}
