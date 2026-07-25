import 'package:flutter/material.dart';

import '../../core/models/activity.dart';
import '../../core/theme.dart';
import '../checkin/checkin_flow_screen.dart';
import '../self_checkin/self_checkin_screen.dart';
import '../late_checkin/late_checkin_screen.dart';

/// Full-detail landing page a card in the activity feed taps through to —
/// the card itself only surfaces a handful of at-a-glance fields, so
/// everything else (description, organizer, dress code, level, ...) lives
/// here instead.
class ActivityDetailScreen extends StatelessWidget {
  const ActivityDetailScreen({
    super.key,
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

  static const _levelLabels = {
    'university': 'ระดับมหาวิทยาลัย',
    'faculty': 'ระดับคณะ',
  };

  static const _checkinMethodLabels = {
    'realtime': 'สแกน QR + GPS + เซลฟี',
    'self_report': 'แนบรูปหลักฐาน (รายงานตนเอง)',
  };

  static const _statusLabels = {
    'draft': 'ยังไม่เปิด',
    'open': 'เปิดรับสมัคร',
    'ongoing': 'กำลังดำเนินการ',
    'full': 'เต็มแล้ว',
    'closed': 'จบไปแล้ว',
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

  String _formatDateTime(String iso) {
    final dt = DateTime.parse(iso).toLocal();
    const months = [
      'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
      'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.',
    ];
    final buddhistYear = dt.year + 543;
    final hh = dt.hour.toString().padLeft(2, '0');
    final mm = dt.minute.toString().padLeft(2, '0');
    return '${dt.day} ${months[dt.month - 1]} $buddhistYear $hh:$mm';
  }

  @override
  Widget build(BuildContext context) {
    final categoryColor = AppColors.categoryColors[activity.activityCategory] ?? Colors.grey;
    final statusColor = _statusColors[activity.status] ?? Colors.grey;
    final statusLabel = _statusLabels[activity.status] ?? activity.status;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final tokens = context.surfaceColors;

    return Scaffold(
      appBar: AppBar(title: const Text('รายละเอียดกิจกรรม')),
      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Stack(
              children: [
                if (activity.bannerUrl != null)
                  Image.network(
                    activity.bannerUrl!,
                    height: 200,
                    width: double.infinity,
                    fit: BoxFit.cover,
                    errorBuilder: (_, _, _) => Container(
                      height: 120,
                      color: categoryColor.withValues(alpha: 0.15),
                    ),
                  )
                else
                  Container(
                    height: 120,
                    color: categoryColor.withValues(alpha: 0.15),
                  ),
                Positioned(
                  right: 12,
                  bottom: 12,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
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
                          decoration: BoxDecoration(color: statusColor, shape: BoxShape.circle),
                        ),
                        const SizedBox(width: 6),
                        Text(
                          statusLabel,
                          style: const TextStyle(
                            fontSize: 12,
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
            Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                spacing: 16,
                children: [
                  Row(
                    children: [
                      Container(
                        width: 8,
                        height: 8,
                        decoration: BoxDecoration(color: categoryColor, shape: BoxShape.circle),
                      ),
                      const SizedBox(width: 6),
                      Text(
                        _categoryLabels[activity.activityCategory] ?? activity.activityCategory,
                        style: TextStyle(fontSize: 13, color: categoryColor, fontWeight: FontWeight.w600),
                      ),
                      if (activity.wasRecentlyUpdatedSignificantly) ...[
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: isDark ? Colors.orange.shade900.withValues(alpha: 0.3) : Colors.orange.shade50,
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Text(
                            'อัปเดตแล้ว',
                            style: TextStyle(
                              fontSize: 10,
                              color: isDark ? Colors.orange.shade300 : Colors.orange.shade700,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (activity.activityCode != null)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 2),
                          child: Text(
                            activity.activityCode!,
                            style: TextStyle(fontSize: 11, color: tokens.textSecondary, fontFamily: 'monospace'),
                          ),
                        ),
                      Text(
                        activity.title,
                        style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 19),
                      ),
                    ],
                  ),
                  if (activity.description != null && activity.description!.isNotEmpty)
                    Text(
                      activity.description!,
                      style: TextStyle(fontSize: 13.5, height: 1.5, color: tokens.textPrimary),
                    ),
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: tokens.surface,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: tokens.border),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      spacing: 14,
                      children: [
                        _InfoRow(
                          icon: Icons.event_outlined,
                          label: 'วันที่จัด',
                          value: activity.startAt == null
                              ? '-'
                              : _formatDateTime(activity.startAt!) +
                                    (activity.endAt != null ? ' – ${_formatDateTime(activity.endAt!)}' : ''),
                        ),
                        if (activity.locationName != null)
                          _InfoRow(
                            icon: Icons.place_outlined,
                            label: 'สถานที่',
                            value: activity.locationName!,
                          ),
                        _InfoRow(
                          icon: activity.usesSelfReportCheckIn ? Icons.photo_camera_outlined : Icons.qr_code_scanner,
                          label: 'วิธีการเช็คชื่อ',
                          value: _checkinMethodLabels[activity.checkinMethod] ?? '-',
                        ),
                        if (activity.organizerName != null)
                          _InfoRow(
                            icon: Icons.groups_outlined,
                            label: 'หน่วยงานจัด',
                            value: activity.organizerName!,
                          ),
                        if (activity.dressCode != null)
                          _InfoRow(
                            icon: Icons.checkroom_outlined,
                            label: 'การแต่งกาย',
                            value: activity.dressCode!,
                          ),
                        if (activity.creditHours != null)
                          _InfoRow(
                            icon: Icons.schedule_outlined,
                            label: 'ชั่วโมงกิจกรรม',
                            value: '${activity.creditHours} ชั่วโมง',
                          ),
                        if (_levelLabels.containsKey(activity.activityLevel))
                          _InfoRow(
                            icon: Icons.school_outlined,
                            label: 'ระดับ',
                            value: _levelLabels[activity.activityLevel]!,
                          ),
                      ],
                    ),
                  ),
                  _buildAction(context),
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
      return _statusBanner(context, 'เช็คชื่อแล้ว', AppColors.statusApproved, Icons.check_circle_outline);
    }

    if (activity.status == 'closed') {
      return SizedBox(
        width: double.infinity,
        child: OutlinedButton.icon(
          onPressed: () => Navigator.of(context).push(
            MaterialPageRoute(builder: (_) => LateCheckInScreen(activity: activity)),
          ),
          icon: const Icon(Icons.history, size: 18),
          label: Text(
            lateStatus == 'pending'
                ? 'รอตรวจสอบคำร้องย้อนหลัง'
                : lateStatus == 'rejected'
                ? 'ยื่นคำร้องใหม่'
                : lateStatus == 'approved'
                ? 'ดูรายละเอียดคำร้อง'
                : 'ขอเช็คชื่อย้อนหลัง',
          ),
        ),
      );
    }

    if (!['open', 'ongoing', 'full'].contains(activity.status)) {
      return const SizedBox.shrink();
    }

    if (activity.usesSelfReportCheckIn) {
      return SizedBox(
        width: double.infinity,
        child: FilledButton.icon(
          onPressed: () => Navigator.of(context).push(
            MaterialPageRoute(builder: (_) => SelfCheckInScreen(activity: activity)),
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
          MaterialPageRoute(builder: (_) => CheckInFlowScreen(activity: activity)),
        ),
        icon: const Icon(Icons.qr_code_scanner, size: 18),
        label: const Text('สแกน QR เช็คชื่อ'),
      ),
    );
  }

  Widget _statusBanner(BuildContext context, String label, Color color, IconData icon) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, size: 18, color: color),
          const SizedBox(width: 8),
          Text(label, style: TextStyle(fontSize: 14, color: color, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.icon, required this.label, required this.value});

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final tokens = context.surfaceColors;

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 18, color: tokens.textSecondary),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: TextStyle(fontSize: 11.5, color: tokens.textSecondary)),
              const SizedBox(height: 2),
              Text(
                value,
                style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.w600, color: tokens.textPrimary),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
