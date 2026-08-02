import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/models/contact.dart';
import '../../core/providers.dart';
import '../../core/theme.dart';
import '../../core/widgets/section_card.dart';
import 'contact_create_screen.dart';
import 'contact_thread_screen.dart';

class ContactThreadsScreen extends ConsumerWidget {
  const ContactThreadsScreen({super.key});

  static const _statusLabel = {'open': 'เปิดอยู่', 'closed': 'ปิดแล้ว'};

  String _relativeTime(DateTime? time) {
    if (time == null) return '';
    final diff = DateTime.now().difference(time);
    if (diff.inMinutes < 1) return 'เมื่อสักครู่';
    if (diff.inMinutes < 60) return '${diff.inMinutes} นาทีที่แล้ว';
    if (diff.inHours < 24) return '${diff.inHours} ชั่วโมงที่แล้ว';
    if (diff.inDays < 30) return '${diff.inDays} วันที่แล้ว';
    return '${time.day}/${time.month}/${time.year}';
  }

  Future<void> _openThread(BuildContext context, WidgetRef ref, int id) async {
    await Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => ContactThreadScreen(threadId: id)),
    );
    ref.invalidate(contactThreadsProvider);
  }

  Future<void> _openCreate(BuildContext context, WidgetRef ref) async {
    final created = await Navigator.of(context).push<bool>(
      MaterialPageRoute(builder: (_) => const ContactCreateScreen()),
    );
    if (created == true) {
      ref.invalidate(contactThreadsProvider);
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final threads = ref.watch(contactThreadsProvider);
    final tokens = context.surfaceColors;

    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: RefreshIndicator(
          onRefresh: () => ref.refresh(contactThreadsProvider.future),
          child: ListView(
            padding: EdgeInsets.zero,
            children: [
              BrandHeader(
                title: 'ติดต่อเจ้าหน้าที่',
                subtitle: 'ส่งข้อความถึงกองพัฒนานักศึกษาและติดตามการตอบกลับ',
                actions: [
                  IconButton(
                    icon: const Icon(Icons.add_comment_outlined, color: Colors.white),
                    tooltip: 'ข้อความใหม่',
                    onPressed: () => _openCreate(context, ref),
                  ),
                ],
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 20),
                child: threads.when(
                  loading: () => const Padding(
                    padding: EdgeInsets.only(top: 80),
                    child: Center(child: CircularProgressIndicator()),
                  ),
                  error: (error, _) => Padding(
                    padding: const EdgeInsets.only(top: 80),
                    child: Center(child: Text('โหลดข้อมูลไม่สำเร็จ: $error')),
                  ),
                  data: (data) {
                    if (data.isEmpty) {
                      return Padding(
                        padding: const EdgeInsets.only(top: 80),
                        child: Center(
                          child: Column(
                            children: [
                              Icon(
                                Icons.chat_bubble_outline,
                                size: 48,
                                color: tokens.textSecondary,
                              ),
                              const SizedBox(height: 12),
                              Text(
                                'ยังไม่มีข้อความถึงเจ้าหน้าที่',
                                style: TextStyle(color: tokens.textSecondary),
                              ),
                            ],
                          ),
                        ),
                      );
                    }

                    return Column(
                      children: data
                          .map((thread) => _ThreadTile(
                                thread: thread,
                                timeLabel: _relativeTime(thread.lastMessageAt),
                                statusLabel: _statusLabel[thread.status] ?? thread.status,
                                onTap: () => _openThread(context, ref, thread.id),
                              ))
                          .toList(),
                    );
                  },
                ),
              ),
              const Padding(
                padding: EdgeInsets.fromLTRB(20, 0, 20, 20),
                child: _ContactInfoSection(),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ThreadTile extends StatelessWidget {
  const _ThreadTile({
    required this.thread,
    required this.timeLabel,
    required this.statusLabel,
    required this.onTap,
  });

  final ContactThread thread;
  final String timeLabel;
  final String statusLabel;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final tokens = context.surfaceColors;
    final statusColor = thread.status == 'open' ? AppColors.statusApproved : tokens.textSecondary;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: tokens.surface,
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
              children: [
                if (thread.unread)
                  Container(
                    margin: const EdgeInsets.only(right: 8),
                    width: 8,
                    height: 8,
                    decoration: const BoxDecoration(
                      color: AppColors.purple600,
                      shape: BoxShape.circle,
                    ),
                  ),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    spacing: 4,
                    children: [
                      Text(
                        thread.subject,
                        style: TextStyle(
                          fontSize: 13.5,
                          fontWeight: thread.unread ? FontWeight.w700 : FontWeight.w600,
                          color: tokens.textPrimary,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      Text(
                        timeLabel,
                        style: TextStyle(fontSize: 11.5, color: tokens.textSecondary),
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: statusColor.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(
                    statusLabel,
                    style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: statusColor),
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

/// Office contact details + FAQ — same content as the web contact page's
/// info panel and FAQ accordion (see partials/contact-info-panel.blade.php
/// and partials/contact-faq.blade.php), fetched from GET /contact/info so
/// both platforms always show identical office details rather than
/// hand-duplicated, driftable copies. FAQ answers are plain text here
/// (not deep-linked to the relevant screen the way the web version's
/// inline links are) to keep this addition scoped.
class _ContactInfoSection extends ConsumerWidget {
  const _ContactInfoSection();

  static const _faqs = [
    (
      'เช็คชื่อกิจกรรมไม่ทันหรือลืมเช็คชื่อ ต้องทำอย่างไร?',
      'เปิดหน้ารายละเอียดกิจกรรมนั้น แล้วกดปุ่ม "เช็คชื่อย้อนหลัง" แนบหลักฐานเพื่อขอเช็คชื่อย้อนหลังได้เลยครับ',
    ),
    (
      'การเช็คชื่อติดธงแดง (flagged) หมายถึงอะไร?',
      'ระบบตรวจพบว่าตำแหน่ง GPS หรือรูปเซลฟีตอนเช็คชื่อไม่ตรงตามเงื่อนไขที่กำหนด เจ้าหน้าที่จะตรวจสอบและอนุมัติหรือปฏิเสธอีกครั้ง ดูสถานะได้ที่หน้าประวัติกิจกรรม',
    ),
    (
      'เข้าร่วมกิจกรรมภายนอกมหาวิทยาลัย ขอเทียบชั่วโมงได้ไหม?',
      'ได้ครับ ยื่นคำร้องพร้อมแนบหลักฐาน (เกียรติบัตร/ภาพเข้าร่วม) ได้ที่หน้าขอชั่วโมงกิจกรรม แท็บ "กิจกรรมภายนอก"',
    ),
    (
      'เป็นผู้นำนักศึกษา (สโมสร/ชมรม) ขอเทียบโอนชั่วโมงตำแหน่งได้อย่างไร?',
      'ยื่นคำร้องพร้อมหลักฐานการดำรงตำแหน่งได้ที่หน้าขอชั่วโมงกิจกรรม แท็บ "เทียบโอนตำแหน่ง" (ทำได้ปีการศึกษาละ 1 ครั้ง)',
    ),
    (
      'ต้องทำกิจกรรมกี่ชั่วโมงถึงจะผ่านเกณฑ์?',
      'เกณฑ์ชั่วโมงกำหนดตามชั้นปีการศึกษา ดูเป้าหมายและความคืบหน้าสะสมของตัวเองได้ที่หน้าแดชบอร์ด',
    ),
  ];

  Future<void> _launch(Uri uri) async {
    if (await canLaunchUrl(uri)) await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final info = ref.watch(contactInfoProvider);
    final tokens = context.surfaceColors;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      spacing: 12,
      children: [
        info.when(
          loading: () => const SizedBox.shrink(),
          error: (error, stack) => const SizedBox.shrink(),
          data: (data) => _InfoCard(info: data, onLaunch: _launch),
        ),
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: tokens.surface,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: tokens.border),
          ),
          child: Theme(
            data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Padding(
                  padding: EdgeInsets.only(bottom: 4),
                  child: Text('คำถามที่พบบ่อย', style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.w700)),
                ),
                ..._faqs.map(
                  (faq) => ExpansionTile(
                    tilePadding: EdgeInsets.zero,
                    childrenPadding: const EdgeInsets.only(bottom: 8),
                    title: Text(faq.$1, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                    children: [
                      Align(
                        alignment: Alignment.centerLeft,
                        child: Text(
                          faq.$2,
                          style: TextStyle(fontSize: 12.5, color: tokens.textSecondary, height: 1.4),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

class _InfoCard extends StatelessWidget {
  const _InfoCard({required this.info, required this.onLaunch});

  final ContactInfo info;
  final Future<void> Function(Uri) onLaunch;

  @override
  Widget build(BuildContext context) {
    final tokens = context.surfaceColors;
    final rows = <Widget>[];

    if (info.phone != null) {
      rows.add(_InfoRow(
        icon: Icons.call_outlined,
        label: 'โทรศัพท์',
        value: info.phone!,
        onTap: () => onLaunch(Uri(scheme: 'tel', path: info.phone)),
      ));
    }
    if (info.email != null) {
      rows.add(_InfoRow(
        icon: Icons.email_outlined,
        label: 'อีเมล',
        value: info.email!,
        onTap: () => onLaunch(Uri(scheme: 'mailto', path: info.email)),
      ));
    }
    if (info.address != null) {
      rows.add(_InfoRow(
        icon: Icons.place_outlined,
        label: 'ที่อยู่',
        value: info.address!,
        onTap: () => onLaunch(Uri.parse(
          'https://www.google.com/maps/search/?api=1&query=${Uri.encodeComponent(info.address!)}',
        )),
      ));
    }
    if (info.hours != null) {
      rows.add(_InfoRow(icon: Icons.schedule_outlined, label: 'เวลาทำการ', value: info.hours!, onTap: null));
    }
    if (info.responseTime != null) {
      rows.add(_InfoRow(
        icon: Icons.chat_bubble_outline,
        label: 'ระยะเวลาตอบกลับแชท',
        value: info.responseTime!,
        onTap: null,
      ));
    }

    if (rows.isEmpty) return const SizedBox.shrink();

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: tokens.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: tokens.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        spacing: 12,
        children: [
          const Text('ช่องทางติดต่ออื่น', style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.w700)),
          ...rows,
        ],
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.icon, required this.label, required this.value, required this.onTap});

  final IconData icon;
  final String label;
  final String value;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final tokens = context.surfaceColors;

    final content = Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      spacing: 10,
      children: [
        Container(
          width: 30,
          height: 30,
          alignment: Alignment.center,
          decoration: BoxDecoration(color: AppColors.purple50, borderRadius: BorderRadius.circular(8)),
          child: Icon(icon, size: 15, color: AppColors.purple600),
        ),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: TextStyle(fontSize: 10.5, color: tokens.textSecondary)),
              Text(
                value,
                style: TextStyle(
                  fontSize: 12.5,
                  fontWeight: FontWeight.w600,
                  color: onTap != null ? AppColors.purple600 : tokens.textPrimary,
                ),
              ),
            ],
          ),
        ),
      ],
    );

    if (onTap == null) return content;
    return InkWell(borderRadius: BorderRadius.circular(8), onTap: onTap, child: content);
  }
}
