import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../models/dashboard.dart';
import '../support_contact.dart';
import '../theme.dart';
import '../../features/hour_requests/hour_requests_screen.dart';

/// Icon per feed item type, shared between the dashboard's preview lists and
/// the full "ดูทั้งหมด" history screen (Student\ActivityHistoryController's
/// mobile counterpart) so both read the same row the same way.
IconData feedTypeIcon(String? type) => switch (type) {
  'checkin' => Icons.qr_code_scanner,
  'external' => Icons.groups_outlined,
  'credit_transfer' => Icons.swap_horiz,
  _ => Icons.event_note,
};

/// Matches dashboard.blade.php's "· `type`" annotation next to the date.
String? feedTypeLabel(DashboardFeedItem item) => switch (item.type) {
  'external' => 'กิจกรรมเทียบชั่วโมง',
  'credit_transfer' => 'เทียบโอนตำแหน่ง',
  _ => item.checkinMethod == 'late_request' ? 'เช็คชื่อย้อนหลัง' : null,
};

/// Opens the same detail a tap on this row would on the web dashboard: the
/// relevant hour-requests tab for external/credit-transfer items, or a
/// selfie/location detail sheet for a plain check-in. Late check-ins don't
/// have a mobile equivalent of the web's edit-request page yet, so they stay
/// non-interactive (matches dashboard_screen.dart's original behavior).
void openFeedItemDetail(BuildContext context, DashboardFeedItem item) {
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

  if (item.checkinMethod != 'late_request' && item.photoUrl != null) {
    showModalBottomSheet(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      builder: (_) => CheckinDetailSheet(item: item),
    );
  }
}

class FeedRow extends StatelessWidget {
  const FeedRow({
    super.key,
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
              // A flagged check-in or rejected request showed the reason and
              // nothing else — same dead-end this fixes on the web
              // dashboard. A nested InkWell (unlike a nested <a> on the web)
              // is fine in Flutter's gesture system: it captures its own tap
              // without also triggering the row's onTap underneath it.
              if (flagReason != null || item.rejectReason != null)
                Padding(
                  padding: const EdgeInsets.only(top: 2),
                  child: InkWell(
                    onTap: () => _contactStaff(
                      subject: flagReason != null
                          ? 'สอบถามเรื่องการเช็คชื่อติดธงแดง: ${item.title}'
                          : 'สอบถามเรื่องคำร้องที่ถูกปฏิเสธ: ${item.title}',
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(
                          Icons.mail_outline,
                          size: 12,
                          color: AppColors.purple600,
                        ),
                        const SizedBox(width: 3),
                        Text(
                          'ติดต่อเจ้าหน้าที่',
                          style: TextStyle(
                            fontSize: 11.5,
                            fontWeight: FontWeight.w600,
                            color: AppColors.purple600,
                          ),
                        ),
                      ],
                    ),
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

  Future<void> _contactStaff({required String subject}) async {
    final uri = Uri(
      scheme: 'mailto',
      path: supportContactEmail,
      query: 'subject=${Uri.encodeComponent(subject)}',
    );
    await launchUrl(uri);
  }
}

/// The mobile equivalent of the web dashboard's Alpine-driven detail popup —
/// selfie, check-in time, location, and hours credited for a realtime/
/// self-report check-in.
class CheckinDetailSheet extends StatelessWidget {
  const CheckinDetailSheet({super.key, required this.item});

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
          DetailRow(label: 'เวลาเช็คชื่อ', value: item.date ?? '-'),
          if (item.locationName != null)
            DetailRow(label: 'สถานที่', value: item.locationName!),
          DetailRow(
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

class DetailRow extends StatelessWidget {
  const DetailRow({super.key, required this.label, required this.value, this.valueColor});

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
