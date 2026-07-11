import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/push_notifications.dart';
import 'core/theme.dart';
import 'features/auth/auth_controller.dart';
import 'features/auth/login_screen.dart';
import 'features/home/home_shell.dart';
import 'features/profile_setup/profile_setup_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp();
  FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);
  runApp(const ProviderScope(child: SrruActivityApp()));
}

class SrruActivityApp extends StatelessWidget {
  const SrruActivityApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'ระบบกิจกรรมนักศึกษา',
      theme: buildAppTheme(),
      darkTheme: buildAppTheme(brightness: Brightness.dark),
      themeMode: ThemeMode.system,
      home: const _AuthGate(),
    );
  }
}

/// Routes to Login / Profile Setup / Home based on AuthController's session
/// state — mirrors GoogleAuthController@callback's web redirect logic
/// (admins -&gt; admin panel is out of scope for this student-only app; an
/// admin signing in here just sees a message, see LoginScreen/AuthController).
class _AuthGate extends ConsumerWidget {
  const _AuthGate();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final session = ref.watch(authControllerProvider);

    return session.when(
      loading: () => const Scaffold(body: Center(child: CircularProgressIndicator())),
      error: (error, _) => const LoginScreen(),
      data: (session) {
        if (session == null) {
          return const LoginScreen();
        }
        if (session.isAdmin) {
          return const _AdminNotSupportedScreen();
        }
        if (!session.profileCompleted) {
          return const ProfileSetupScreen();
        }

        return const HomeShell();
      },
    );
  }
}

class _AdminNotSupportedScreen extends ConsumerWidget {
  const _AdminNotSupportedScreen();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Scaffold(
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text('บัญชีผู้ดูแลระบบกรุณาใช้งานผ่านเว็บแอดมิน', textAlign: TextAlign.center),
              const SizedBox(height: 16),
              FilledButton(
                onPressed: () => ref.read(authControllerProvider.notifier).logout(),
                child: const Text('ออกจากระบบ'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
