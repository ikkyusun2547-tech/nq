import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/models/app_notification.dart';
import '../../core/notification_routing.dart';
import '../../core/providers.dart';
import '../../core/theme.dart';
import '../../core/widgets/section_card.dart';

class NotificationsScreen extends ConsumerStatefulWidget {
  const NotificationsScreen({super.key});

  @override
  ConsumerState<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends ConsumerState<NotificationsScreen> {
  static const _icons = {
    'check': Icons.check_circle_outline,
    'reject': Icons.cancel_outlined,
    'flag': Icons.flag_outlined,
    'credit': Icons.swap_horiz,
    'external': Icons.groups_outlined,
  };

  static const _iconColors = {
    'check': AppColors.statusApproved,
    'reject': AppColors.statusRejected,
    'flag': AppColors.statusPending,
    'credit': Color(0xFF3B82F6),
    'external': Color(0xFF8B5CF6),
  };

  // Dismissible items must disappear from the list on this exact build (not
  // after the delete request round-trips) or Flutter throws "A dismissed
  // Dismissible widget is still part of the tree" the next time this screen
  // rebuilds with the same key still present.
  final _dismissedIds = <String>{};

  String _relativeTime(DateTime time) {
    final diff = DateTime.now().difference(time);
    if (diff.inMinutes < 1) return 'เมื่อสักครู่';
    if (diff.inMinutes < 60) return '${diff.inMinutes} นาทีที่แล้ว';
    if (diff.inHours < 24) return '${diff.inHours} ชั่วโมงที่แล้ว';
    if (diff.inDays < 30) return '${diff.inDays} วันที่แล้ว';
    return '${time.day}/${time.month}/${time.year}';
  }

  @override
  Widget build(BuildContext context) {
    final page = ref.watch(notificationsPageProvider);
    final tokens = context.surfaceColors;

    Future<void> markAllRead() async {
      await ref.read(notificationsRepositoryProvider).markAllRead();
      ref.invalidate(notificationsPageProvider);
      ref.invalidate(unreadNotificationCountProvider);
    }

    Future<void> markRead(AppNotification notification) async {
      if (notification.read) return;
      await ref.read(notificationsRepositoryProvider).markRead(notification.id);
      ref.invalidate(notificationsPageProvider);
      ref.invalidate(unreadNotificationCountProvider);
    }

    void openNotification(AppNotification notification) {
      markRead(notification);
      ref
          .read(homeTabIndexProvider.notifier)
          .set(homeTabIndexForUrl(notification.url));
      Navigator.of(context).pop();
    }

    Future<void> deleteOne(AppNotification notification) async {
      setState(() => _dismissedIds.add(notification.id));
      try {
        await ref.read(notificationsRepositoryProvider).delete(notification.id);
      } finally {
        ref.invalidate(unreadNotificationCountProvider);
      }
    }

    Future<void> deleteAll() async {
      final confirmed = await showDialog<bool>(
        context: context,
        builder: (_) => AlertDialog(
          title: const Text('ลบการแจ้งเตือนทั้งหมด?'),
          content: const Text('ลบแล้วไม่สามารถกู้คืนได้'),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: const Text('ยกเลิก'),
            ),
            FilledButton(
              style: FilledButton.styleFrom(
                backgroundColor: AppColors.statusRejected,
              ),
              onPressed: () => Navigator.of(context).pop(true),
              child: const Text('ลบทั้งหมด'),
            ),
          ],
        ),
      );
      if (confirmed != true) return;

      await ref.read(notificationsRepositoryProvider).deleteAll();
      _dismissedIds.clear();
      ref.invalidate(notificationsPageProvider);
      ref.invalidate(unreadNotificationCountProvider);
    }

    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: RefreshIndicator(
          onRefresh: () {
            setState(_dismissedIds.clear);
            return ref.refresh(notificationsPageProvider.future);
          },
          child: ListView(
            padding: EdgeInsets.zero,
            children: [
              BrandHeader(
                title: 'การแจ้งเตือน',
                subtitle: 'ความเคลื่อนไหวล่าสุดของกิจกรรมและคำร้องของคุณ',
                leading: IconButton(
                  icon: const Icon(Icons.arrow_back, color: Colors.white),
                  onPressed: () => Navigator.of(context).pop(),
                ),
                actions: [
                  if ((page.value?.unreadCount ?? 0) > 0)
                    IconButton(
                      icon: const Icon(Icons.done_all, color: Colors.white),
                      tooltip: 'อ่านทั้งหมดแล้ว',
                      onPressed: markAllRead,
                    ),
                  if ((page.value?.notifications ?? const []).isNotEmpty)
                    IconButton(
                      icon: const Icon(Icons.delete_sweep_outlined, color: Colors.white),
                      tooltip: 'ลบทั้งหมด',
                      onPressed: deleteAll,
                    ),
                ],
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 20),
                child: page.when(
                  loading: () => const Padding(
                    padding: EdgeInsets.only(top: 80),
                    child: Center(child: CircularProgressIndicator()),
                  ),
                  error: (error, _) => Padding(
                    padding: const EdgeInsets.only(top: 80),
                    child: Center(child: Text('โหลดข้อมูลไม่สำเร็จ: $error')),
                  ),
                  data: (data) {
                    final visible = data.notifications
                        .where((n) => !_dismissedIds.contains(n.id))
                        .toList();

                    if (visible.isEmpty) {
                      return Padding(
                        padding: const EdgeInsets.only(top: 80),
                        child: Center(
                          child: Column(
                            children: [
                              Icon(
                                Icons.notifications_none,
                                size: 48,
                                color: tokens.textSecondary,
                              ),
                              const SizedBox(height: 12),
                              Text(
                                'ยังไม่มีการแจ้งเตือน',
                                style: TextStyle(color: tokens.textSecondary),
                              ),
                            ],
                          ),
                        ),
                      );
                    }

                    return Column(
                          children: visible
                              .map(
                                (n) => Dismissible(
                                  key: ValueKey(n.id),
                                  direction: DismissDirection.endToStart,
                                  background: Container(
                                    margin: const EdgeInsets.only(bottom: 12),
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 20,
                                    ),
                                    alignment: Alignment.centerRight,
                                    decoration: BoxDecoration(
                                      color: AppColors.statusRejected,
                                      borderRadius: BorderRadius.circular(16),
                                    ),
                                    child: const Icon(
                                      Icons.delete_outline,
                                      color: Colors.white,
                                    ),
                                  ),
                                  onDismissed: (_) => deleteOne(n),
                                  child: _NotificationTile(
                                    notification: n,
                                    icon: _icons[n.icon] ?? Icons.notifications_outlined,
                                    color: _iconColors[n.icon] ?? AppColors.purple600,
                                    timeLabel: _relativeTime(n.createdAt),
                                    onTap: () => openNotification(n),
                                  ),
                                ),
                              )
                              .toList(),
                        );
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _NotificationTile extends StatelessWidget {
  const _NotificationTile({
    required this.notification,
    required this.icon,
    required this.color,
    required this.timeLabel,
    required this.onTap,
  });

  final AppNotification notification;
  final IconData icon;
  final Color color;
  final String timeLabel;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final tokens = context.surfaceColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: notification.read
            ? tokens.surface
            : (isDark
                  ? AppColors.purple900.withValues(alpha: 0.25)
                  : AppColors.purple50),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: tokens.border),
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: 0.12),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(icon, size: 18, color: color),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    spacing: 4,
                    children: [
                      Text(
                        notification.title,
                        style: TextStyle(
                          fontSize: 13.5,
                          fontWeight: notification.read
                              ? FontWeight.w600
                              : FontWeight.w700,
                          color: tokens.textPrimary,
                        ),
                      ),
                      Text(
                        notification.body,
                        style: TextStyle(
                          fontSize: 12.5,
                          color: tokens.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        timeLabel,
                        style: TextStyle(
                          fontSize: 11,
                          color: tokens.textSecondary.withValues(alpha: 0.8),
                        ),
                      ),
                    ],
                  ),
                ),
                if (!notification.read)
                  Container(
                    margin: const EdgeInsets.only(top: 4, left: 6),
                    width: 8,
                    height: 8,
                    decoration: const BoxDecoration(
                      color: AppColors.purple600,
                      shape: BoxShape.circle,
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
