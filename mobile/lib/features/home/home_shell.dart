import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/providers.dart';
import '../../core/widgets/app_bottom_nav.dart';
import '../activities/activities_screen.dart';
import '../dashboard/dashboard_screen.dart';
import '../hour_requests/hour_requests_screen.dart';
import '../profile/profile_screen.dart';

/// Tab index lives in homeTabIndexProvider (not local State) so a tapped
/// push notification can switch tabs from outside the widget tree — see
/// PushNotificationService._handleNotificationTap.
class HomeShell extends ConsumerStatefulWidget {
  const HomeShell({super.key});

  static const _screens = [
    DashboardScreen(),
    ActivitiesScreen(),
    HourRequestsScreen(),
    ProfileScreen(),
  ];

  @override
  ConsumerState<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends ConsumerState<HomeShell>
    with WidgetsBindingObserver {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  // A push that arrives while the app is backgrounded only reaches the
  // system tray (see PushNotificationService's background handler) — nothing
  // refreshes the bell badge until something re-watches it. Resuming from
  // background is exactly the moment a student would expect it to be caught
  // up, so that's the trigger here (foreground-arrival is handled directly
  // in PushNotificationService._showForegroundNotification instead).
  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      ref.invalidate(unreadNotificationCountProvider);
      ref.invalidate(notificationsPageProvider);
    }
  }

  @override
  Widget build(BuildContext context) {
    final index = ref.watch(homeTabIndexProvider);

    return Scaffold(
      body: IndexedStack(index: index, children: HomeShell._screens),
      bottomNavigationBar: AppBottomNav(
        currentIndex: index,
        onTap: (i) => ref.read(homeTabIndexProvider.notifier).set(i),
      ),
    );
  }
}
