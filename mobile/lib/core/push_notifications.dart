import 'dart:developer' as developer;

import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

import 'api_client.dart';

/// Talks to Api\DeviceTokenController — POST/DELETE /device-token.
class DeviceTokenRepository {
  DeviceTokenRepository({required this.apiClient});

  final ApiClient apiClient;

  Future<void> register(String token) async {
    await apiClient.dio.post(
      '/device-token',
      data: {'token': token, 'platform': 'android'},
    );
  }

  Future<void> unregister(String token) async {
    await apiClient.dio.delete('/device-token', data: {'token': token});
  }
}

/// Must be a top-level (or static) function — the OS relaunches a bare
/// isolate to run this when a data message arrives while the app is fully
/// terminated. Our messages always carry a `notification` payload too, which
/// FCM already renders in the system tray on its own in that state, so
/// there's nothing left to do here.
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {}

/// FCM only auto-displays a system notification for background/terminated
/// app states; while the app is in the foreground it hands the message to
/// us instead, so we show it ourselves via flutter_local_notifications —
/// otherwise a foreground student would never see approval/rejection
/// notices arrive in real time.
class PushNotificationService {
  PushNotificationService({required this.deviceTokenRepository});

  final DeviceTokenRepository deviceTokenRepository;
  final _localNotifications = FlutterLocalNotificationsPlugin();
  bool _initialized = false;

  Future<void> initialize() async {
    if (_initialized) return;
    _initialized = true;

    await _localNotifications.initialize(
      settings: const InitializationSettings(
        android: AndroidInitializationSettings('@mipmap/ic_launcher'),
      ),
    );

    await FirebaseMessaging.instance.requestPermission();

    FirebaseMessaging.onMessage.listen(_showForegroundNotification);
    FirebaseMessaging.instance.onTokenRefresh.listen(_registerToken);
  }

  Future<void> registerCurrentToken() async {
    final token = await FirebaseMessaging.instance.getToken();
    if (token != null) await _registerToken(token);
  }

  Future<void> unregisterCurrentToken() async {
    final token = await FirebaseMessaging.instance.getToken();
    if (token == null) return;

    try {
      await deviceTokenRepository.unregister(token);
    } catch (e) {
      developer.log('Failed to unregister device token', error: e);
    }
  }

  Future<void> _registerToken(String token) async {
    try {
      await deviceTokenRepository.register(token);
    } catch (e) {
      developer.log('Failed to register device token', error: e);
    }
  }

  Future<void> _showForegroundNotification(RemoteMessage message) async {
    final notification = message.notification;
    if (notification == null) return;

    await _localNotifications.show(
      id: message.hashCode.abs() % 100000,
      title: notification.title,
      body: notification.body,
      notificationDetails: const NotificationDetails(
        android: AndroidNotificationDetails(
          'default_channel',
          'การแจ้งเตือนทั่วไป',
          importance: Importance.high,
          priority: Priority.high,
        ),
      ),
    );
  }
}
