import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:uuid/uuid.dart';

/// Persists the Sanctum bearer token and a device_uuid generated once per
/// install, mirroring the web app's localStorage-based `srru_device_uuid`
/// (see resources/views/student/checkin.blade.php) but backed by Keychain /
/// EncryptedSharedPreferences instead.
class AuthStorage {
  AuthStorage({FlutterSecureStorage? storage})
    : _storage = storage ?? const FlutterSecureStorage();

  static const _tokenKey = 'srru_auth_token';
  static const _deviceUuidKey = 'srru_device_uuid';

  final FlutterSecureStorage _storage;

  Future<String?> getToken() => _storage.read(key: _tokenKey);

  Future<void> setToken(String token) => _storage.write(key: _tokenKey, value: token);

  Future<void> clearToken() => _storage.delete(key: _tokenKey);

  Future<String> getOrCreateDeviceUuid() async {
    final existing = await _storage.read(key: _deviceUuidKey);
    if (existing != null) {
      return existing;
    }

    final generated = const Uuid().v4();
    await _storage.write(key: _deviceUuidKey, value: generated);

    return generated;
  }
}
