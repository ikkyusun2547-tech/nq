import 'package:dio/dio.dart';

import '../../core/api_client.dart';
import '../../core/models/credit_transfer_request.dart';

class CreditTransfersRepository {
  CreditTransfersRepository({required this.apiClient});

  final ApiClient apiClient;

  Future<List<CreditTransferRequest>> fetch() async {
    final response = await apiClient.dio.get('/credit-transfers');
    final items = ((response.data as Map<String, dynamic>)['data'] as List)
        .cast<Map<String, dynamic>>();

    return items.map(CreditTransferRequest.fromJson).toList();
  }

  Future<List<CreditTransferPosition>> fetchPositions() async {
    final response = await apiClient.dio.get('/credit-transfers/positions');
    final items = ((response.data as Map<String, dynamic>)['data'] as List)
        .cast<Map<String, dynamic>>();

    return items.map(CreditTransferPosition.fromJson).toList();
  }

  Future<void> submit({
    required String position,
    required int academicYear,
    required String proofImagePath,
  }) async {
    await apiClient.dio.post(
      '/credit-transfers',
      data: FormData.fromMap({
        'position': position,
        'academic_year': academicYear,
        'proof_image': await MultipartFile.fromFile(
          proofImagePath,
          filename: 'proof.jpg',
        ),
      }),
    );
  }

  /// Only valid while the request is still 'pending' — the backend rejects
  /// (422) any attempt to cancel one an admin has already reviewed.
  Future<void> cancel(int id) async {
    await apiClient.dio.delete('/credit-transfers/$id');
  }
}
