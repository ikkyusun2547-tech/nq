import 'package:dio/dio.dart';

import 'api_exception.dart';
import 'auth_storage.dart';

/// Shared Dio instance for every screen. Attaches the Sanctum bearer token,
/// `Accept: application/json` (so Laravel's exception handler renders
/// validation/auth errors as JSON instead of an HTML redirect — see the
/// backend plan's "Existing Form Requests are reused unchanged" note), and
/// `X-Locale` (read by App\Http\Middleware\SetLocaleApi).
///
/// Base URL is set at build/run time via
/// `--dart-define=API_BASE_URL=https://your-host/api` so dev/staging/prod
/// never need a code change.
class ApiClient {
  ApiClient({AuthStorage? authStorage, String? locale})
    : _authStorage = authStorage ?? AuthStorage(),
      _locale = locale ?? 'th' {
    dio = Dio(BaseOptions(baseUrl: baseUrl, connectTimeout: const Duration(seconds: 15)));

    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          options.headers['Accept'] = 'application/json';
          options.headers['X-Locale'] = _locale;

          final token = await _authStorage.getToken();
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }

          handler.next(options);
        },
        onError: (error, handler) {
          final response = error.response;
          if (response != null) {
            handler.next(
              error.copyWith(error: ApiException.fromResponse(response.statusCode ?? 0, response.data)),
            );
            return;
          }

          handler.next(error);
        },
      ),
    );
  }

  static const baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://hypnotism-thesaurus-pants.ngrok-free.dev/api',
  );

  final AuthStorage _authStorage;
  final String _locale;
  late final Dio dio;
}
