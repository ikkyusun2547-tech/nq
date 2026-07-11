/// Typed wrapper around the API's JSON error shape: `{message, error_code?,
/// errors?}` — every controller in the Laravel app returns this same shape
/// on 4xx responses (see e.g. Api\AuthController, Api\Student\*Controller).
class ApiException implements Exception {
  ApiException({required this.statusCode, required this.message, this.errorCode, this.errors});

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
