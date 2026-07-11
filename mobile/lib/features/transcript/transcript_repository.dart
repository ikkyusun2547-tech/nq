import 'package:dio/dio.dart';

import '../../core/api_client.dart';

class TranscriptRepository {
  TranscriptRepository({required this.apiClient});

  final ApiClient apiClient;

  /// Fetches the PDF as bytes rather than opening the bare URL in a WebView —
  /// a WebView can't reliably attach the Authorization: Bearer header needed
  /// for this authenticated download (see the backend plan's Phase 8 note).
  Future<List<int>> downloadBytes() async {
    final response = await apiClient.dio.get<List<int>>(
      '/transcript',
      options: Options(responseType: ResponseType.bytes),
    );

    return response.data!;
  }
}
