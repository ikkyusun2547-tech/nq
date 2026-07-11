import 'package:dio/dio.dart';

import '../../core/api_client.dart';

class CheckInResult {
  CheckInResult({required this.status, this.distanceMeters, required this.message});

  final String status;
  final int? distanceMeters;
  final String message;

  bool get autoApproved => status == 'auto_approved';
}

class CheckInRepository {
  CheckInRepository({required this.apiClient});

  final ApiClient apiClient;

  Future<CheckInResult> submit({
    required String qrToken,
    required double lat,
    required double lng,
    required String deviceUuid,
    required String photoPath,
  }) async {
    final response = await apiClient.dio.post(
      '/checkin',
      data: FormData.fromMap({
        'qr_token': qrToken,
        'location_lat': lat,
        'location_lng': lng,
        'device_uuid': deviceUuid,
        'photo': await MultipartFile.fromFile(photoPath, filename: 'selfie.jpg'),
      }),
    );

    final data = response.data as Map<String, dynamic>;

    return CheckInResult(
      status: data['status'] as String,
      distanceMeters: data['distance_meters'] as int?,
      message: data['message'] as String,
    );
  }
}
