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

  /// Looked up right after a QR scan (the token's plain first segment is
  /// the activity id — see the backend's DynamicQrTokenGenerator) so the UI
  /// knows whether to even ask for GPS before moving on. Defaults to true
  /// (the safer assumption) if the lookup fails for any reason.
  Future<bool> fetchRequiresGps(String qrToken) async {
    final activityId = qrToken.split('.').first;
    if (!RegExp(r'^\d+$').hasMatch(activityId)) return true;

    try {
      final response = await apiClient.dio.get(
        '/activities/$activityId/checkin-requirements',
      );
      final data = response.data as Map<String, dynamic>;
      return data['requires_gps'] != false;
    } catch (_) {
      return true;
    }
  }

  Future<CheckInResult> submit({
    required String qrToken,
    required double? lat,
    required double? lng,
    required String deviceUuid,
    required String photoPath,
  }) async {
    final response = await apiClient.dio.post(
      '/checkin',
      data: FormData.fromMap({
        'qr_token': qrToken,
        'location_lat': ?lat,
        'location_lng': ?lng,
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
