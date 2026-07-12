import 'dart:developer' as developer;

import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/widgets.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'api_client.dart';
import 'notification_routing.dart';
import 'providers.dart';

/// Attached to MaterialApp in main.dart so a tapped notification can pop
/// back to the HomeShell root even if the user had other screens pushed on
/// top when it arrived.
final navigatorKey = GlobalKey<NavigatorState>();

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
  PushNotificationService({required this.deviceTokenRepository, required this.ref});

  final DeviceTokenRepository deviceTokenRepository;
  final Ref ref;
  final _localNotifications = FlutterLocalNotificationsPlugin();
  bool _initialized = false;

  Future<void> initialize() async {
    if (_initialized) return;
    _initialized = true;
    // No Firebase web app is registered for this project — push is Android-only.
    if (kIsWeb) return;

    await _localNotifications.initialize(
      settings: const InitializationSettings(
        android: AndroidInitializationSettings('@mipmap/ic_launcher'),
      ),
      // Taps on the notification we show ourselves for foreground messages
      // (below) never reach FirebaseMessaging.onMessageOpenedApp — that only
      // fires for the system tray notification FCM auto-displays while
      // backgrounded/terminated. This is the tap handler for our own one.
      onDidReceiveNotificationResponse: (response) => _routeToUrl(response.payload),
    );

    await FirebaseMessaging.instance.requestPermission();

    FirebaseMessaging.onMessage.listen(_showForegroundNotification);
    FirebaseMessaging.instance.onTokenRefresh.listen(_registerToken);

    // Tapped from the system tray while backgrounded...
    FirebaseMessaging.onMessageOpenedApp.listen(_handleNotificationTap);
    // ...or from fully terminated (cold start via notification tap).
    final initialMessage = await FirebaseMessaging.instance.getInitialMessage();
    if (initialMessage != null) _handleNotificationTap(initialMessage);
  }

  Future<void> registerCurrentToken() async {
    if (kIsWeb) return;
    final token = await FirebaseMessaging.instance.getToken();
    if (token != null) await _registerToken(token);
  }

  Future<void> unregisterCurrentToken() async {
    if (kIsWeb) return;
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
      // Carried through to onDidReceiveNotificationResponse above when the
      // user taps this notification.
      payload: message.data['url'] as String?,
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

  /// Tapped from the system tray while backgrounded (FCM auto-displayed it).
  void _handleNotificationTap(RemoteMessage message) {
    _routeToUrl(message.data['url'] as String?);
  }

  void _routeToUrl(String? url) {
    if (url == null) return;

    ref.read(homeTabIndexProvider.notifier).set(homeTabIndexForUrl(url));
    navigatorKey.currentState?.popUntil((route) => route.isFirst);
  }
}
