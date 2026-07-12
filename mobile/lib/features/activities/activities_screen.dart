import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/models/activity.dart';
import '../../core/providers.dart';
import '../../core/theme.dart';
import '../../core/widgets/section_card.dart';
import '../checkin/checkin_flow_screen.dart';
import '../self_checkin/self_checkin_screen.dart';
import '../late_checkin/late_checkin_screen.dart';

class ActivitiesScreen extends ConsumerStatefulWidget {
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

  static const _categories = {
    null: 'ทุกหมวดหมู่',
    'culture': 'ทำนุบำรุงศิลปวัฒนธรรม',
    'academic': 'วิชาการ',
    'sports': 'กีฬาและส่งเสริมสุขภาพ',
    'volunteer': 'จิตอาสา/บำเพ็ญประโยชน์',
    'ethics': 'คุณธรรมจริยธรรม',
  };

  @override
  ConsumerState<ActivitiesScreen> createState() => _ActivitiesScreenState();
}

class _ActivitiesScreenState extends ConsumerState<ActivitiesScreen> {
  final _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    // Only need to redraw for the clear (x) button's visibility — the
    // provider itself is updated on submit, not per keystroke.
    _searchController.addListener(() => setState(() {}));
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _openFilterSheet() {
    showModalBottomSheet(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      builder: (_) => const _FilterSheet(),
    );
  }

  @override
  Widget build(BuildContext context) {
    final statusGroup = ref.watch(activitiesStatusGroupProvider);
    final levelFilter = ref.watch(activitiesLevelFilterProvider);
    final categoryFilter = ref.watch(activitiesCategoryFilterProvider);
    final page = ref.watch(activitiesPageProvider);
    final tokens = context.surfaceColors;
    final activeFilterCount =
        (levelFilter != null ? 1 : 0) + (categoryFilter != null ? 1 : 0);

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
              // Search + filter-sheet trigger on one row, status tabs below —
              // the level/category pickers used to sit here too as two more
              // always-visible scrolling chip rows, which buried the actual
              // activity list under four rows of controls before content
              // even started. They now live behind the filter button instead.
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 10),
                child: Row(
                  spacing: 8,
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _searchController,
                        textInputAction: TextInputAction.search,
                        onSubmitted: (value) => ref
                            .read(activitiesSearchProvider.notifier)
                            .set(value.trim()),
                        decoration: InputDecoration(
                          hintText: 'ค้นหากิจกรรม',
                          prefixIcon: const Icon(Icons.search, size: 20),
                          suffixIcon: _searchController.text.isEmpty
                              ? null
                              : IconButton(
                                  icon: const Icon(Icons.close, size: 18),
                                  onPressed: () {
                                    _searchController.clear();
                                    ref
                                        .read(activitiesSearchProvider.notifier)
                                        .set('');
                                  },
                                ),
                          isDense: true,
                          filled: true,
                          fillColor: tokens.surface,
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(14),
                            borderSide: BorderSide(color: tokens.border),
                          ),
                        ),
                      ),
                    ),
                    Material(
                      color: activeFilterCount > 0
                          ? AppColors.purple700
                          : tokens.surface,
                      borderRadius: BorderRadius.circular(14),
                      child: InkWell(
                        borderRadius: BorderRadius.circular(14),
                        onTap: _openFilterSheet,
                        child: Container(
                          width: 46,
                          height: 46,
                          alignment: Alignment.center,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(14),
                            border: activeFilterCount > 0
                                ? null
                                : Border.all(color: tokens.border),
                          ),
                          child: Stack(
                            clipBehavior: Clip.none,
                            children: [
                              Icon(
                                Icons.tune,
                                size: 20,
                                color: activeFilterCount > 0
                                    ? Colors.white
                                    : tokens.textSecondary,
                              ),
                              if (activeFilterCount > 0)
                                Positioned(
                                  top: -6,
                                  right: -6,
                                  child: Container(
                                    padding: const EdgeInsets.all(3),
                                    constraints: const BoxConstraints(
                                      minWidth: 16,
                                      minHeight: 16,
                                    ),
                                    decoration: const BoxDecoration(
                                      color: AppColors.green500,
                                      shape: BoxShape.circle,
                                    ),
                                    child: Text(
                                      '$activeFilterCount',
                                      textAlign: TextAlign.center,
                                      style: const TextStyle(
                                        fontSize: 9,
                                        fontWeight: FontWeight.w700,
                                        color: AppColors.purple950,
                                      ),
                                    ),
                                  ),
                                ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 0, 20, 8),
                child: Container(
                  padding: const EdgeInsets.all(4),
                  decoration: BoxDecoration(
                    color: tokens.surface,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: tokens.border),
                  ),
                  child: Row(
                    children: ActivitiesScreen._statusGroups.entries.map((e) {
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

/// Level + category pickers, moved out of the always-visible screen body
/// (see ActivitiesScreen._openFilterSheet) into a sheet so they only take up
/// space when the student actually wants to narrow the list down.
class _FilterSheet extends ConsumerWidget {
  const _FilterSheet();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final levelFilter = ref.watch(activitiesLevelFilterProvider);
    final categoryFilter = ref.watch(activitiesCategoryFilterProvider);
    final tokens = context.surfaceColors;
    final hasFilters = levelFilter != null || categoryFilter != null;

    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'ตัวกรอง',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                ),
                if (hasFilters)
                  TextButton(
                    onPressed: () {
                      ref.read(activitiesLevelFilterProvider.notifier).set(null);
                      ref
                          .read(activitiesCategoryFilterProvider.notifier)
                          .set(null);
                    },
                    child: const Text('ล้างตัวกรอง'),
                  ),
              ],
            ),
            const SizedBox(height: 12),
            Text(
              'ระดับ',
              style: TextStyle(
                fontSize: 12.5,
                fontWeight: FontWeight.w600,
                color: tokens.textSecondary,
              ),
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: ActivitiesScreen._activityLevels.entries.map((e) {
                final selected = e.key == levelFilter;

                return _FilterChip(
                  label: e.value,
                  selected: selected,
                  color: AppColors.purple700,
                  onTap: () => ref
                      .read(activitiesLevelFilterProvider.notifier)
                      .set(e.key),
                );
              }).toList(),
            ),
            const SizedBox(height: 20),
            Text(
              'หมวดหมู่',
              style: TextStyle(
                fontSize: 12.5,
                fontWeight: FontWeight.w600,
                color: tokens.textSecondary,
              ),
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: ActivitiesScreen._categories.entries.map((e) {
                final selected = e.key == categoryFilter;
                final color = e.key == null
                    ? tokens.textSecondary
                    : (AppColors.categoryColors[e.key] ?? Colors.grey);

                return _FilterChip(
                  label: e.value,
                  selected: selected,
                  color: color,
                  dotColor: e.key != null ? color : null,
                  onTap: () => ref
                      .read(activitiesCategoryFilterProvider.notifier)
                      .set(e.key),
                );
              }).toList(),
            ),
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: () => Navigator.of(context).pop(),
                child: const Text('เสร็จสิ้น'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _FilterChip extends StatelessWidget {
  const _FilterChip({
    required this.label,
    required this.selected,
    required this.color,
    required this.onTap,
    this.dotColor,
  });

  final String label;
  final bool selected;
  final Color color;
  final Color? dotColor;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final tokens = context.surfaceColors;

    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
        decoration: BoxDecoration(
          color: selected ? color.withValues(alpha: 0.15) : Colors.transparent,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: selected ? color : tokens.border),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (dotColor != null) ...[
              Container(
                width: 6,
                height: 6,
                decoration: BoxDecoration(color: dotColor, shape: BoxShape.circle),
              ),
              const SizedBox(width: 6),
            ],
            Text(
              label,
              style: TextStyle(
                fontSize: 12.5,
                fontWeight: FontWeight.w600,
                color: selected ? color : tokens.textSecondary,
              ),
            ),
          ],
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
        'เช็คชื่อแล้ว',
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
          label: const Text('ขอเช็คชื่อย้อนหลัง'),
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
        label: const Text('สแกน QR เช็คชื่อ'),
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
