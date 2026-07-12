import 'dart:async';

import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api_exception.dart';
import '../../core/models/app_user.dart';
import '../../core/providers.dart';
import 'auth_repository.dart';

/// Null session means logged out. Kept deliberately small — screens read
/// `session.value?.user.profileCompleted` to decide whether to route to
/// Profile Setup or the Dashboard, mirroring the web app's
/// GoogleAuthController@callback redirect logic.
class AuthSession {
  AuthSession({
    required this.user,
    required this.profileCompleted,
    required this.isAdmin,
  });

  final AppUser user;
  final bool profileCompleted;
  final bool isAdmin;

  AuthSession copyWith({AppUser? user, bool? profileCompleted}) {
    return AuthSession(
      user: user ?? this.user,
      profileCompleted: profileCompleted ?? this.profileCompleted,
      isAdmin: isAdmin,
    );
  }
}

class AuthController extends AsyncNotifier<AuthSession?> {
  late final AuthRepository _repository;

  @override
  Future<AuthSession?> build() async {
    _repository = ref.watch(authRepositoryProvider);

    final token = await ref.watch(authStorageProvider).getToken();
    if (token == null) {
      return null;
    }

    try {
      final user = await _repository.me();
      unawaited(_registerPush());

      return AuthSession(
        user: user,
        profileCompleted: user.profileCompleted,
        isAdmin: user.isAdmin,
      );
    } catch (_) {
      // Token is stale/revoked (e.g. logged out from another device) — treat
      // as logged out rather than surfacing an error on app start.
      await ref.read(authStorageProvider).clearToken();
      return null;
    }
  }

  Future<void> loginWithGoogleIdToken(String idToken) async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() async {
      final result = await _unwrapErrors(
        () => _repository.loginWithGoogle(idToken),
      );
      unawaited(_registerPush());

      return AuthSession(
        user: result.user,
        profileCompleted: result.profileCompleted,
        isAdmin: result.isAdmin,
      );
    });
  }

  /// Debug-only login path — see AuthRepository.loginAsTestUser.
  Future<void> loginAsTestUser(int userId) async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() async {
      final result = await _unwrapErrors(
        () => _repository.loginAsTestUser(userId),
      );
      unawaited(_registerPush());

      return AuthSession(
        user: result.user,
        profileCompleted: result.profileCompleted,
        isAdmin: result.isAdmin,
      );
    });
  }

  // AsyncValue.guard captures whatever this throws as `state`'s error — but
  // a failed dio call always throws DioException, never the ApiException an
  // interceptor stashed inside it (see api_exception.dart's
  // DioExceptionUnwrap). Without this, login_screen's `error is ApiException`
  // check on the resulting AsyncError never matches, and a specific backend
  // message (banned account, wrong email domain, ...) never reaches the UI.
  Future<T> _unwrapErrors<T>(Future<T> Function() action) async {
    try {
      return await action();
    } on DioException catch (e) {
      throw e.asApiException;
    }
  }

  Future<void> refreshProfile() async {
    final user = await _repository.me();
    state = AsyncData(
      AuthSession(
        user: user,
        profileCompleted: user.profileCompleted,
        isAdmin: user.isAdmin,
      ),
    );
  }

  Future<void> logout() async {
    // Must happen before _repository.logout() revokes the Sanctum token —
    // the unregister call itself needs a still-valid Authorization header.
    await ref.read(pushNotificationServiceProvider).unregisterCurrentToken();
    await _repository.logout();
    state = const AsyncData(null);
  }

  Future<void> _registerPush() async {
    try {
      final service = ref.read(pushNotificationServiceProvider);
      await service.initialize();
      await service.registerCurrentToken();
    } catch (_) {
      // Push notifications are a nice-to-have — never block login/session
      // restore on this.
    }
  }
}

final authControllerProvider =
    AsyncNotifierProvider<AuthController, AuthSession?>(AuthController.new);
