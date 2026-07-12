import '../../core/api_client.dart';
import '../../core/auth_storage.dart';
import '../../core/models/app_user.dart';

class GoogleLoginResult {
  GoogleLoginResult({
    required this.user,
    required this.profileCompleted,
    required this.isAdmin,
  });

  final AppUser user;
  final bool profileCompleted;
  final bool isAdmin;
}

/// Talks to Api\AuthController — POST /auth/google, POST /auth/logout,
/// GET /auth/me.
class AuthRepository {
  AuthRepository({required this.apiClient, required this.authStorage});

  final ApiClient apiClient;
  final AuthStorage authStorage;

  Future<GoogleLoginResult> loginWithGoogle(String idToken) async {
    final response = await apiClient.dio.post(
      '/auth/google',
      data: {'id_token': idToken},
    );

    return _sessionFromResponse(response.data as Map<String, dynamic>);
  }

  /// Debug-only shortcut hitting the local-only GET /_test-login/{user} route
  /// (see routes/api.php) — mints a real Sanctum token for a seeded account
  /// without Google OAuth, so the app can be previewed as a student without
  /// a real @srru.ac.th Google login. Callers must gate this behind
  /// kDebugMode themselves; the server enforces the local-env restriction.
  Future<GoogleLoginResult> loginAsTestUser(int userId) async {
    final response = await apiClient.dio.get('/_test-login/$userId');

    return _sessionFromResponse(response.data as Map<String, dynamic>);
  }

  Future<GoogleLoginResult> _sessionFromResponse(
    Map<String, dynamic> data,
  ) async {
    await authStorage.setToken(data['token'] as String);

    return GoogleLoginResult(
      user: AppUser.fromJson(data['user'] as Map<String, dynamic>),
      profileCompleted: data['profile_completed'] as bool,
      isAdmin: data['is_admin'] as bool,
    );
  }

  Future<AppUser> me() async {
    final response = await apiClient.dio.get('/auth/me');
    final data = response.data as Map<String, dynamic>;

    return AppUser.fromJson(data['user'] as Map<String, dynamic>);
  }

  Future<void> logout() async {
    try {
      await apiClient.dio.post('/auth/logout');
    } finally {
      await authStorage.clearToken();
    }
  }
}
