import 'package:dio/dio.dart';

/// Typed wrapper around the API's JSON error shape: `{message, error_code?,
/// errors?}` — every controller in the Laravel app returns this same shape
/// on 4xx responses (see e.g. Api\AuthController, Api\Student\*Controller).
class ApiException implements Exception {
  ApiException({
    required this.statusCode,
    required this.message,
    this.errorCode,
    this.errors,
  });

  final int statusCode;
  final String message;
  final String? errorCode;
  final Map<String, dynamic>? errors;

  factory ApiException.fromResponse(int statusCode, dynamic data) {
    if (data is Map<String, dynamic>) {
      return ApiException(
        statusCode: statusCode,
        message: data['message']?.toString() ?? 'Request failed',
        errorCode: data['error_code']?.toString(),
        errors: data['errors'] as Map<String, dynamic>?,
      );
    }

    return ApiException(statusCode: statusCode, message: 'Request failed');
  }

  @override
  String toString() => 'ApiException($statusCode, $errorCode, $message)';
}

/// Dio always throws DioException, never the custom `.error` object an
/// interceptor stashes on it — so `on ApiException catch (e)` around a
/// `dio.post/get/delete` call never actually matches; it silently falls
/// through to a generic catch-all instead, hiding every specific backend
/// validation message (e.g. "already checked in to this activity") behind
/// "something went wrong, try again". ApiClient's interceptor (see
/// api_client.dart) wraps every 4xx/5xx response's `.error` in an
/// ApiException; this unwraps it back out at the call site.
extension DioExceptionUnwrap on DioException {
  ApiException get asApiException {
    final wrapped = error;
    if (wrapped is ApiException) return wrapped;

    return ApiException(
      statusCode: 0,
      message: message ?? 'การเชื่อมต่อล้มเหลว กรุณาลองใหม่อีกครั้ง',
    );
  }
}
