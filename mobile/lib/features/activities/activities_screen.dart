import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/models/activity.dart';
import '../../core/providers.dart';
import '../../core/theme.dart';
import '../../core/widgets/section_card.dart';
import '../checkin/checkin_flow_screen.dart';
import '../self_checkin/self_checkin_screen.dart';
import '../late_checkin/late_checkin_screen.dart';

class ActivitiesScreen extends ConsumerWidget {
  const ActivitiesScreen({super.key});

  static const _statusGroups = {
    'open': 'เปิดรับ',
    'upcoming': 'เร็วๆ นี้',
    'ended': 'สิ้นสุดแล้ว',
  };

  static const _activityLevels = {
    null: 'ทั้งหมด',
    'university': 'ระดับมหาวิทยาลัย',
    'faculty': 'ระดับคณะ',
  };

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final statusGroup = ref.watch(activitiesStatusGroupProvider);
    final levelFilter = ref.watch(activitiesLevelFilterProvider);
    final page = ref.watch(activitiesPageProvider);
    final tokens = context.surfaceColors;

    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: RefreshIndicator(
          onRefresh: () => ref.refresh(activitiesPageProvider.future),
          child: ListView(
            padding: EdgeInsets.zero,
            children: [
              BrandHeader(
                title: 'กิจกรรม',
                subtitle: 'เลือกกิจกรรมที่เข้าร่วมได้',
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 8),
                child: Container(
                  padding: const EdgeInsets.all(4),
                  decoration: BoxDecoration(
                    color: tokens.surface,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: tokens.border),
                  ),
                  child: Row(
                    children: _statusGroups.entries.map((e) {
                      final selected = e.key == statusGroup;

                      return Expanded(
                        child: GestureDetector(
                          onTap: () => ref
                              .read(activitiesStatusGroupProvider.notifier)
                              .set(e.key),
                          child: AnimatedContainer(
                            duration: const Duration(milliseconds: 200),
                            padding: const EdgeInsets.symmetric(vertical: 10),
                            decoration: BoxDecoration(
                              color: selected
                                  ? AppColors.purple700
                                  : Colors.transparent,
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Text(
                              e.value,
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: selected
                                    ? Colors.white
                                    : tokens.textSecondary,
                              ),
                            ),
                          ),
                        ),
                      );
                    }).toList(),
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: SizedBox(
                  height: 36,
                  child: ListView(
                    scrollDirection: Axis.horizontal,
                    padding: const EdgeInsets.symmetric(horizontal: 20),
                    children: _activityLevels.entries.map((e) {
                      final selected = e.key == levelFilter;

                      return Padding(
                        padding: const EdgeInsets.only(right: 8),
                        child: GestureDetector(
                          onTap: () => ref
                              .read(activitiesLevelFilterProvider.notifier)
                              .set(e.key),
                          child: AnimatedContainer(
                            duration: const Duration(milliseconds: 200),
                            padding: const EdgeInsets.symmetric(
                              horizontal: 14,
                              vertical: 8,
                            ),
                            decoration: BoxDecoration(
                              color: selected
                                  ? AppColors.purple700
                                  : tokens.surface,
                              borderRadius: BorderRadius.circular(20),
                              border: Border.all(
                                color: selected
                                    ? AppColors.purple700
                                    : tokens.border,
                              ),
                            ),
                            child: Text(
                              e.value,
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                                color: selected
                                    ? Colors.white
                                    : tokens.textSecondary,
                              ),
                            ),
                          ),
                        ),
                      );
                    }).toList(),
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 20),
                child: page.when(
                  loading: () => const Padding(
                    padding: EdgeInsets.only(top: 80),
                    child: Center(child: CircularProgressIndicator()),
                  ),
                  error: (error, _) => Padding(
                    padding: const EdgeInsets.only(top: 80),
                    child: Center(child: Text('โหลดข้อมูลไม่สำเร็จ: $error')),
                  ),
                  data: (data) => data.activities.isEmpty
                      ? Padding(
                          padding: const EdgeInsets.only(top: 80),
                          child: Center(
                            child: Text(
                              'ไม่มีกิจกรรมในหมวดนี้',
                              style: TextStyle(color: tokens.textSecondary),
                            ),
                          ),
                        )
                      : Column(
                          children: data.activities.map((activity) {
                            final checkedIn = data.checkedInActivityIds
                                .contains(activity.id);
                            final lateStatus = data
                                .lateCheckinStatuses[activity.id.toString()];

                            return _ActivityCard(
                              activity: activity,
                              checkedIn: checkedIn,
                              lateStatus: lateStatus,
                            );
                          }).toList(),
                        ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ActivityCard extends StatelessWidget {
  const _ActivityCard({
    required this.activity,
    required this.checkedIn,
    this.lateStatus,
  });

  final Activity activity;
  final bool checkedIn;
  final String? lateStatus;

  static const _categoryLabels = {
    'culture': 'ทำนุบำรุงศิลปวัฒนธรรม',
    'academic': 'วิชาการ',
    'sports': 'กีฬาและส่งเสริมสุขภาพ',
    'volunteer': 'จิตอาสา/บำเพ็ญประโยชน์',
    'ethics': 'คุณธรรมจริยธรรม',
  };

  static const _statusLabels = {
    'draft': 'ร่าง',
    'open': 'เปิดรับ',
    'ongoing': 'กำลังดำเนินการ',
    'full': 'เต็มแล้ว',
    'closed': 'ปิดรับแล้ว',
    'cancelled': 'ยกเลิก',
  };

  static const _statusColors = {
    'draft': Color(0xFF9CA3AF),
    'open': AppColors.statusApproved,
    'ongoing': Color(0xFF3B82F6),
    'full': AppColors.statusPending,
    'closed': Color(0xFF6B7280),
    'cancelled': AppColors.statusRejected,
  };

  @override
  Widget build(BuildContext context) {
    final categoryColor =
        AppColors.categoryColors[activity.activityCategory] ?? Colors.grey;
    final statusColor = _statusColors[activity.status] ?? Colors.grey;
    final statusLabel = _statusLabels[activity.status] ?? activity.status;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final tokens = context.surfaceColors;

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: BoxDecoration(
        color: tokens.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: tokens.border),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDark ? 0.25 : 0.04),
            blurRadius: 12,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(17),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Stack(
              children: [
                if (activity.bannerUrl != null)
                  Image.network(
                    activity.bannerUrl!,
                    height: 140,
                    width: double.infinity,
                    fit: BoxFit.cover,
                    errorBuilder: (_, _, _) => Container(
                      height: 56,
                      color: categoryColor.withValues(alpha: 0.15),
                    ),
                    loadingBuilder: (context, child, progress) {
                      if (progress == null) return child;
                      return Container(
                        height: 140,
                        color: isDark
                            ? AppColors.purple900.withValues(alpha: 0.3)
                            : AppColors.purple50,
                        child: const Center(
                          child: CircularProgressIndicator(strokeWidth: 2),
                        ),
                      );
                    },
                  )
                else
                  Container(
                    height: 56,
                    color: categoryColor.withValues(alpha: 0.15),
                  ),
                Positioned(
                  top: 10,
                  right: 10,
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 5,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.black.withValues(alpha: 0.55),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Container(
                          width: 7,
                          height: 7,
                          decoration: BoxDecoration(
                            color: statusColor,
                            shape: BoxShape.circle,
                          ),
                        ),
                        const SizedBox(width: 6),
                        Text(
                          statusLabel,
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                            letterSpacing: 0.2,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
            IntrinsicHeight(
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Container(width: 5, color: categoryColor),
                  Expanded(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  activity.title,
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w700,
                                    fontSize: 15,
                                  ),
                                ),
                              ),
                              if (activity.wasRecentlyUpdatedSignificantly)
                                Container(
                                  margin: const EdgeInsets.only(left: 8),
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 8,
                                    vertical: 3,
                                  ),
                                  decoration: BoxDecoration(
                                    color: isDark
                                        ? Colors.orange.shade900.withValues(
                                            alpha: 0.3,
                                          )
                                        : Colors.orange.shade50,
                                    borderRadius: BorderRadius.circular(20),
                                  ),
                                  child: Text(
                                    'อัปเดตแล้ว',
                                    style: TextStyle(
                                      fontSize: 10,
                                      color: isDark
                                          ? Colors.orange.shade300
                                          : Colors.orange.shade700,
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ),
                            ],
                          ),
                          const SizedBox(height: 6),
                          Text(
                            _categoryLabels[activity.activityCategory] ??
                                activity.activityCategory,
                            style: TextStyle(
                              fontSize: 12,
                              color: categoryColor,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                          const SizedBox(height: 8),
                          if (activity.locationName != null)
                            Row(
                              children: [
                                Icon(
                                  Icons.place_outlined,
                                  size: 14,
                                  color: tokens.textSecondary,
                                ),
                                const SizedBox(width: 4),
                                Expanded(
                                  child: Text(
                                    activity.locationName!,
                                    style: TextStyle(
                                      fontSize: 12,
                                      color: tokens.textSecondary,
                                    ),
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                          if (activity.creditHours != null) ...[
                            const SizedBox(height: 4),
                            Row(
                              children: [
                                Icon(
                                  Icons.schedule_outlined,
                                  size: 14,
                                  color: tokens.textSecondary,
                                ),
                                const SizedBox(width: 4),
                                Text(
                                  '${activity.creditHours} ชั่วโมง',
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: tokens.textSecondary,
                                  ),
                                ),
                              ],
                            ),
                          ],
                          const SizedBox(height: 12),
                          _buildAction(context),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildAction(BuildContext context) {
    if (checkedIn) {
      return _statusChip(
        'เช็กชื่อแล้ว',
        AppColors.statusApproved,
        Icons.check_circle_outline,
      );
    }

    if (lateStatus != null) {
      return _statusChip(
        'คำร้องย้อนหลัง: $lateStatus',
        AppColors.statusPending,
        Icons.history,
      );
    }

    if (activity.status == 'closed') {
      return SizedBox(
        width: double.infinity,
        child: OutlinedButton.icon(
          onPressed: () => Navigator.of(context).push(
            MaterialPageRoute(
              builder: (_) => LateCheckInScreen(activity: activity),
            ),
          ),
          icon: const Icon(Icons.history, size: 18),
          label: const Text('ขอเช็กชื่อย้อนหลัง'),
        ),
      );
    }

    if (activity.status != 'open' &&
        activity.status != 'ongoing' &&
        activity.status != 'full') {
      return const SizedBox.shrink();
    }

    if (activity.usesSelfReportCheckIn) {
      return SizedBox(
        width: double.infinity,
        child: FilledButton.icon(
          onPressed: () => Navigator.of(context).push(
            MaterialPageRoute(
              builder: (_) => SelfCheckInScreen(activity: activity),
            ),
          ),
          icon: const Icon(Icons.camera_alt_outlined, size: 18),
          label: const Text('รายงานตนเอง'),
        ),
      );
    }

    return SizedBox(
      width: double.infinity,
      child: FilledButton.icon(
        onPressed: () => Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => CheckInFlowScreen(activity: activity),
          ),
        ),
        icon: const Icon(Icons.qr_code_scanner, size: 18),
        label: const Text('สแกน QR เช็กชื่อ'),
      ),
    );
  }

  Widget _statusChip(String label, Color color, IconData icon) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: color),
          const SizedBox(width: 6),
          Flexible(
            child: Text(
              label,
              style: TextStyle(
                fontSize: 12,
                color: color,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
