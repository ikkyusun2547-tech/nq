import 'package:dio/dio.dart';

import '../../core/api_client.dart';

class SelfCheckInRepository {
  SelfCheckInRepository({required this.apiClient});

  final ApiClient apiClient;

  Future<String> submit({required int activityId, required String photoPath}) async {
    final response = await apiClient.dio.post(
      '/activities/$activityId/self-checkin',
      data: FormData.fromMap({'photo': await MultipartFile.fromFile(photoPath, filename: 'proof.jpg')}),
    );

    return (response.data as Map<String, dynamic>)['message'] as String;
  }
}
