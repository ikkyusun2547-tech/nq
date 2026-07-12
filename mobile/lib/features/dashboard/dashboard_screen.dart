import 'dart:io';
import 'dart:typed_data';

import 'package:file_saver/file_saver.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';

import '../../core/models/app_user.dart';
import '../../core/models/dashboard.dart';
import '../../core/providers.dart';
import '../../core/theme.dart';
import '../../core/widgets/section_card.dart';
import '../auth/auth_controller.dart';
import '../checkin/checkin_flow_screen.dart';
import '../hour_requests/hour_requests_screen.dart';
import '../notifications/notifications_screen.dart';
import '../transcript/transcript_screen.dart';

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  static const _categoryLabels = {
    'culture': 'ทำนุบำรุงศิลปวัฒนธรรม',
    'academic': 'วิชาการ',
    'sports': 'กีฬาและส่งเสริมสุขภาพ',
    'volunteer': 'จิตอาสา/บำเพ็ญประโยชน์',
    'ethics': 'คุณธรรมจริยธรรม',
  };

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashboard = ref.watch(dashboardDataProvider);
    final user = ref.watch(authControllerProvider).value?.user;

    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: RefreshIndicator(
          onRefresh: () => ref.refresh(dashboardDataProvider.future),
          child: dashboard.when(
            loading: () => const Center(child: CircularProgressIndicator()),
            error: (error, _) => ListView(
              children: [
                const SizedBox(height: 120),
                Center(child: Text('โหลดข้อมูลไม่สำเร็จ: $error')),
              ],
            ),
            data: (data) => ListView(
              padding: EdgeInsets.zero,
              children: [
                BrandHeader(
                  title: data.summary.isCleared
                      ? 'ผ่านเกณฑ์กิจกรรมแล้ว 🎉'
                      : 'พอร์ตกิจกรรม',
                  subtitle: data.summary.isCleared
                      ? 'คุณทำกิจกรรมครบตามเกณฑ์ที่กำหนดแล้ว'
                      : 'ภาพรวมกิจกรรมและชั่วโมงสะสมของคุณ',
                  actions: const [_NotificationBellButton()],
                  footer: user != null
                      ? _StudentIdentitySection(
                          user: user,
                          currentYear: data.summary.currentYear,
                        )
                      : null,
                ),
                Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    spacing: 16,
                    children: [
                      const _QuickActions(),
                      _ProgressCard(summary: data.summary),
                      if (data.summary.currentYear != null &&
                          data.summary.yearlyTargetHours != null)
                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 4),
                          child: Text(
                            'เป้าหมายชั่วโมงกิจกรรมของชั้นปีที่ '
                            '${data.summary.currentYear} คือ '
                            '${data.summary.yearlyTargetHours} ชั่วโมง/ปี',
                            style: TextStyle(
                              fontSize: 12,
                              color: context.surfaceColors.textSecondary,
                            ),
                          ),
                        ),
                      SectionCard(
                        icon: Icons.pie_chart_outline,
                        title: 'ชั่วโมงตามหมวดหมู่',
                        children: data.summary.categoryHours.entries
                            .map(
                              (e) => Row(
                                children: [
                                  Container(
                                    width: 10,
                                    height: 10,
                                    decoration: BoxDecoration(
                                      color:
                                          AppColors.categoryColors[e.key] ??
                                          Colors.grey,
                                      shape: BoxShape.circle,
                                    ),
                                  ),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Text(
                                      _categoryLabels[e.key] ?? e.key,
                                      style: const TextStyle(fontSize: 13),
                                    ),
                                  ),
                                  Text(
                                    '${e.value} ชม.',
                                    style: const TextStyle(
                                      fontSize: 13,
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ],
                              ),
                            )
                            .toList(),
                      ),
                      const _TranscriptActionsCard(),
                      if (data.pending.isNotEmpty)
                        _FeedSection(title: 'รอตรวจสอบ', items: data.pending),
                      if (data.approved.isNotEmpty)
                        _FeedSection(
                          title: 'อนุมัติแล้ว',
                          items: data.approved,
                        ),
                      if (data.rejected.isNotEmpty)
                        _FeedSection(title: 'ถูกปฏิเสธ', items: data.rejected),
                    ],
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

/// Identifies whose dashboard this is at a glance — one unified glass card:
/// avatar/name/student-id up top, a divider, then icon-labelled rows for
/// year/faculty/major, since the same gradient bar is otherwise generic
/// across every student.
class _StudentIdentitySection extends StatelessWidget {
  const _StudentIdentitySection({
    required this.user,
    required this.currentYear,
  });

  final AppUser user;
  final int? currentYear;

  @override
  Widget build(BuildContext context) {
    final infoRows = [
      if (currentYear != null)
        (Icons.school_outlined, 'นักศึกษาชั้นปีที่ $currentYear'),
      if (user.faculty?.nameTh != null)
        (Icons.account_balance_outlined, user.faculty!.nameTh),
      if (user.major?.nameTh != null)
        (Icons.menu_book_outlined, user.major!.nameTh),
    ];

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 22,
                backgroundColor: Colors.white.withValues(alpha: 0.25),
                backgroundImage: user.avatarUrl != null
                    ? NetworkImage(user.avatarUrl!)
                    : null,
                child: user.avatarUrl == null
                    ? const Icon(Icons.person, color: Colors.white, size: 22)
                    : null,
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      user.nameThai ?? user.name,
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                        color: Colors.white,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (user.studentId != null) ...[
                      const SizedBox(height: 2),
                      Text(
                        'รหัสนักศึกษา ${user.studentId}',
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.white.withValues(alpha: 0.8),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
          if (infoRows.isNotEmpty) ...[
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 12),
              child: Divider(
                height: 1,
                color: Colors.white.withValues(alpha: 0.2),
              ),
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              spacing: 8,
              children: infoRows
                  .map(
                    (row) => Row(
                      children: [
                        Icon(
                          row.$1,
                          size: 15,
                          color: Colors.white.withValues(alpha: 0.75),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            row.$2,
                            style: TextStyle(
                              fontSize: 12.5,
                              color: Colors.white.withValues(alpha: 0.9),
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  )
                  .toList(),
            ),
          ],
        ],
      ),
    );
  }
}

class _NotificationBellButton extends ConsumerWidget {
  const _NotificationBellButton();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final unreadCount = ref.watch(unreadNotificationCountProvider).value ?? 0;

    return Padding(
      padding: const EdgeInsets.only(left: 8),
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          IconButton(
            icon: const Icon(Icons.notifications_outlined, color: Colors.white),
            onPressed: () async {
              await Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => const NotificationsScreen()),
              );
              ref.invalidate(unreadNotificationCountProvider);
            },
          ),
          if (unreadCount > 0)
            Positioned(
              top: 6,
              right: 6,
              child: Container(
                padding: const EdgeInsets.all(3),
                constraints: const BoxConstraints(minWidth: 16, minHeight: 16),
                decoration: const BoxDecoration(
                  color: AppColors.statusRejected,
                  shape: BoxShape.circle,
                ),
                child: Text(
                  unreadCount > 9 ? '9+' : '$unreadCount',
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontSize: 9,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

/// Mirrors the three shortcut buttons on the web dashboard (scan QR, submit
/// external activity, submit credit transfer) so students don't have to hop
/// to bottom-nav tabs first.
class _QuickActions extends StatelessWidget {
  const _QuickActions();

  @override
  Widget build(BuildContext context) {
    return IntrinsicHeight(
      child: Row(
        spacing: 10,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Expanded(
            child: _QuickActionButton(
              label: 'สแกน QR\nเช็คชื่อ',
              filled: true,
              onTap: () => Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => const CheckInFlowScreen()),
              ),
            ),
          ),
          Expanded(
            child: _QuickActionButton(
              label: 'ยื่นกิจกรรม\nภายนอก',
              onTap: () => Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (_) =>
                      const HourRequestsScreen(initialTab: 0, standalone: true),
                ),
              ),
            ),
          ),
          Expanded(
            child: _QuickActionButton(
              label: 'เทียบโอน\nชั่วโมง',
              onTap: () => Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (_) =>
                      const HourRequestsScreen(initialTab: 1, standalone: true),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _QuickActionButton extends StatelessWidget {
  const _QuickActionButton({
    required this.label,
    required this.onTap,
    this.filled = false,
  });

  final String label;
  final VoidCallback onTap;
  final bool filled;

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final tokens = context.surfaceColors;

    return Material(
      color: filled ? AppColors.green500 : tokens.surface,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 10),
          alignment: Alignment.center,
          decoration: filled
              ? null
              : BoxDecoration(
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: tokens.border),
                ),
          child: Text(
            label,
            textAlign: TextAlign.center,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              fontSize: 11.5,
              height: 1.3,
              fontWeight: FontWeight.w700,
              color: filled
                  ? AppColors.purple950
                  : (isDark ? AppColors.purple400 : AppColors.purple700),
            ),
          ),
        ),
      ),
    );
  }
}

class _ProgressCard extends StatelessWidget {
  const _ProgressCard({required this.summary});

  final DashboardSummary summary;

  @override
  Widget build(BuildContext context) {
    final tokens = context.surfaceColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: tokens.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: tokens.border),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDark ? 0.2 : 0.04),
            blurRadius: 16,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        children: [
          Expanded(
            child: _ProgressRing(
              label: 'กิจกรรม',
              value: summary.totalActivities,
              target: summary.requiredActivities,
              color: AppColors.purple600,
            ),
          ),
          Container(width: 1, height: 72, color: tokens.border),
          Expanded(
            child: _ProgressRing(
              label: 'ชั่วโมง',
              value: summary.totalHours,
              target: summary.requiredHours,
              color: AppColors.green600,
            ),
          ),
        ],
      ),
    );
  }
}

class _ProgressRing extends StatelessWidget {
  const _ProgressRing({
    required this.label,
    required this.value,
    required this.target,
    required this.color,
  });

  final String label;
  final int value;
  final int target;
  final Color color;

  @override
  Widget build(BuildContext context) {
    final progress = target > 0 ? (value / target).clamp(0, 1).toDouble() : 0.0;

    return Column(
      children: [
        SizedBox(
          width: 76,
          height: 76,
          child: Stack(
            alignment: Alignment.center,
            children: [
              SizedBox(
                width: 76,
                height: 76,
                child: CircularProgressIndicator(
                  value: progress,
                  strokeWidth: 7,
                  backgroundColor: color.withValues(alpha: 0.12),
                  valueColor: AlwaysStoppedAnimation(color),
                  strokeCap: StrokeCap.round,
                ),
              ),
              Text(
                '$value',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                  color: color,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 8),
        Text(
          label,
          style: TextStyle(
            fontSize: 12,
            color: context.surfaceColors.textSecondary,
          ),
        ),
        Text(
          'เป้าหมาย $target',
          style: TextStyle(
            fontSize: 11,
            color: context.surfaceColors.textSecondary,
          ),
        ),
      ],
    );
  }
}

class _FeedSection extends StatelessWidget {
  const _FeedSection({required this.title, required this.items});

  final String title;
  final List<DashboardFeedItem> items;

  static const _typeIcons = {
    'checkin': Icons.qr_code_scanner,
    'external': Icons.groups_outlined,
    'credit_transfer': Icons.swap_horiz,
  };

  // Matches dashboard.blade.php's "· <type>" annotation next to the date.
  static String? _typeLabel(DashboardFeedItem item) => switch (item.type) {
    'external' => 'กิจกรรมเทียบชั่วโมง',
    'credit_transfer' => 'เทียบโอนตำแหน่ง',
    _ => item.checkinMethod == 'late_request' ? 'เช็คชื่อย้อนหลัง' : null,
  };

  void _onTap(BuildContext context, DashboardFeedItem item) {
    switch (item.type) {
      case 'external':
        Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) =>
                const HourRequestsScreen(initialTab: 0, standalone: true),
          ),
        );
        return;
      case 'credit_transfer':
        Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) =>
                const HourRequestsScreen(initialTab: 1, standalone: true),
          ),
        );
        return;
    }

    // Plain check-ins (realtime/self-report) carry a selfie + location to
    // show, same as the web dashboard's detail popup. Late check-ins don't
    // have a mobile equivalent of the web's edit-request page yet, so they
    // stay non-interactive here.
    if (item.checkinMethod != 'late_request' && item.photoUrl != null) {
      showModalBottomSheet(
        context: context,
        showDragHandle: true,
        isScrollControlled: true,
        builder: (_) => _CheckinDetailSheet(item: item),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final statusColor = switch (title) {
      'รอตรวจสอบ' => AppColors.statusPending,
      'อนุมัติแล้ว' => AppColors.statusApproved,
      _ => AppColors.statusRejected,
    };

    return SectionCard(
      icon: Icons.checklist_outlined,
      title: title,
      children: items
          .map(
            (item) => _FeedRow(
              item: item,
              statusColor: statusColor,
              icon: _typeIcons[item.type] ?? Icons.event_note,
              typeLabel: _typeLabel(item),
              onTap: title == 'รอตรวจสอบ' ? null : () => _onTap(context, item),
            ),
          )
          .toList(),
    );
  }
}

class _FeedRow extends StatelessWidget {
  const _FeedRow({
    required this.item,
    required this.statusColor,
    required this.icon,
    required this.typeLabel,
    required this.onTap,
  });

  final DashboardFeedItem item;
  final Color statusColor;
  final IconData icon;
  final String? typeLabel;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final subtitle = [
      item.rejectReason ?? item.date,
      if (typeLabel != null) typeLabel,
    ].whereType<String>().join(' · ');

    // Matches dashboard.blade.php: a flagged check-in's reason (GPS out of
    // bounds, device sharing suspected, ...) shows on its own line, not
    // folded into the date/type subtitle above.
    final flagReason = item.flagReason;

    final row = Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 32,
          height: 32,
          decoration: BoxDecoration(
            color: statusColor.withValues(alpha: 0.12),
            shape: BoxShape.circle,
          ),
          child: Icon(icon, size: 16, color: statusColor),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                item.title ?? '',
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                ),
                overflow: TextOverflow.ellipsis,
              ),
              if (subtitle.isNotEmpty)
                Text(
                  subtitle,
                  style: TextStyle(
                    fontSize: 12,
                    color: context.surfaceColors.textSecondary,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              if (flagReason != null)
                Padding(
                  padding: const EdgeInsets.only(top: 2),
                  child: Text(
                    'เหตุผลที่ต้องตรวจสอบ: $flagReason',
                    style: const TextStyle(
                      fontSize: 11.5,
                      color: AppColors.statusPending,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
            ],
          ),
        ),
        if (item.hours != null)
          Text(
            '${item.hours} ชม.',
            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
          ),
      ],
    );

    if (onTap == null) return row;

    return InkWell(
      borderRadius: BorderRadius.circular(12),
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 2),
        child: row,
      ),
    );
  }
}

/// The mobile equivalent of the web dashboard's Alpine-driven detail popup —
/// selfie, check-in time, location, and hours credited for a realtime/
/// self-report check-in.
class _CheckinDetailSheet extends StatelessWidget {
  const _CheckinDetailSheet({required this.item});

  final DashboardFeedItem item;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.fromLTRB(
        20,
        0,
        20,
        20 + MediaQuery.of(context).viewInsets.bottom,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            item.title ?? '',
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 12),
          if (item.photoUrl != null)
            ClipRRect(
              borderRadius: BorderRadius.circular(16),
              child: Image.network(
                item.photoUrl!,
                width: double.infinity,
                fit: BoxFit.contain,
              ),
            ),
          const SizedBox(height: 12),
          _DetailRow(label: 'เวลาเช็คชื่อ', value: item.date ?? '-'),
          if (item.locationName != null)
            _DetailRow(label: 'สถานที่', value: item.locationName!),
          _DetailRow(
            label: 'ชั่วโมงที่ได้รับ',
            value: '${item.hours ?? 0} ชม.',
            valueColor: AppColors.green600,
          ),
          SizedBox(height: MediaQuery.of(context).padding.bottom),
        ],
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  const _DetailRow({required this.label, required this.value, this.valueColor});

  final String label;
  final String value;
  final Color? valueColor;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 13,
              color: context.surfaceColors.textSecondary,
            ),
          ),
          Text(
            value,
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: valueColor,
            ),
          ),
        ],
      ),
    );
  }
}

/// Quick view/share/download access to the transcript PDF right from the
/// dashboard, so students don't need a dedicated bottom-nav tab for it.
class _TranscriptActionsCard extends ConsumerStatefulWidget {
  const _TranscriptActionsCard();

  @override
  ConsumerState<_TranscriptActionsCard> createState() =>
      _TranscriptActionsCardState();
}

class _TranscriptActionsCardState
    extends ConsumerState<_TranscriptActionsCard> {
  bool _busy = false;

  Future<List<int>> _fetchBytes() =>
      ref.read(transcriptRepositoryProvider).downloadBytes();

  void _showMessage(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text(message)));
  }

  Future<void> _runAction(Future<void> Function(List<int> bytes) action) async {
    setState(() => _busy = true);
    try {
      final bytes = await _fetchBytes();
      await action(bytes);
    } catch (_) {
      _showMessage('ดำเนินการไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  void _view() {
    Navigator.of(
      context,
    ).push(MaterialPageRoute(builder: (_) => const TranscriptScreen()));
  }

  Future<void> _share() => _runAction((bytes) async {
    final tempDir = await getTemporaryDirectory();
    final file = File('${tempDir.path}/activity-summary.pdf');
    await file.writeAsBytes(bytes);
    await SharePlus.instance.share(ShareParams(files: [XFile(file.path)]));
  });

  Future<void> _download() => _runAction((bytes) async {
    // saveFile() writes to the app's private external-files dir (invisible
    // to the user in Files/Downloads). saveAs() opens the native "Save As"
    // picker so the student actually chooses a visible destination.
    final path = await FileSaver.instance.saveAs(
      name: 'activity-summary',
      bytes: Uint8List.fromList(bytes),
      fileExtension: 'pdf',
      mimeType: MimeType.pdf,
    );
    if (path != null) _showMessage('บันทึกไฟล์ลงเครื่องสำเร็จ');
  });

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final tokens = context.surfaceColors;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      decoration: BoxDecoration(
        color: tokens.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: tokens.border),
      ),
      child: Row(
        children: [
          Icon(
            Icons.description_outlined,
            size: 18,
            color: isDark ? AppColors.purple400 : AppColors.purple700,
          ),
          const SizedBox(width: 10),
          const Expanded(
            child: Text(
              'ใบสรุปกิจกรรม (PDF)',
              style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
              overflow: TextOverflow.ellipsis,
            ),
          ),
          _MiniIconButton(
            icon: Icons.visibility_outlined,
            tooltip: 'ดู',
            onPressed: _busy ? null : _view,
          ),
          _MiniIconButton(
            icon: Icons.share_outlined,
            tooltip: 'แชร์',
            onPressed: _busy ? null : _share,
          ),
          _MiniIconButton(
            icon: Icons.download_outlined,
            tooltip: 'ดาวน์โหลด',
            loading: _busy,
            onPressed: _busy ? null : _download,
          ),
        ],
      ),
    );
  }
}

class _MiniIconButton extends StatelessWidget {
  const _MiniIconButton({
    required this.icon,
    required this.tooltip,
    required this.onPressed,
    this.loading = false,
  });

  final IconData icon;
  final String tooltip;
  final VoidCallback? onPressed;
  final bool loading;

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final color = isDark ? AppColors.purple400 : AppColors.purple700;

    return IconButton(
      onPressed: onPressed,
      tooltip: tooltip,
      visualDensity: VisualDensity.compact,
      icon: loading
          ? SizedBox(
              width: 16,
              height: 16,
              child: CircularProgressIndicator(strokeWidth: 2, color: color),
            )
          : Icon(icon, size: 19, color: color),
    );
  }
}
