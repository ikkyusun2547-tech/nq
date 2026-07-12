/// Every notification's `url` is the web route it points to (see
/// BaseNotification::toFcm()/NotificationResource server-side) — mapped here
/// to the matching HomeShell bottom-nav tab index. Shared by both the push
/// notification tap handler (push_notifications.dart) and the in-app
/// notification list (features/notifications/notifications_screen.dart) so
/// tapping a notification lands you in the same place either way.
///
/// Only the top-level tab is deep-linked (not e.g. the hour-requests
/// external/credit sub-tab or activities status filter) — those live in
/// per-screen state, not providers, so they aren't safely settable before
/// that screen exists.
int homeTabIndexForUrl(String? url) {
  if (url == null) return 0;

  final path = Uri.tryParse(url)?.path ?? '';
  if (path.startsWith('/hour-requests')) return 2;
  if (path.startsWith('/activities')) return 1;
  if (path.startsWith('/profile')) return 3;
  return 0;
}
