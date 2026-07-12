import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';

import '../../core/api_exception.dart';
import '../../core/models/credit_transfer_request.dart';
import '../../core/providers.dart';
import '../../core/theme.dart';
import '../../core/widgets/section_card.dart';

/// External activities and credit-transfer requests share the same shape of
/// workflow (submit proof, wait for approval, see status), so they live on
/// one screen with a tab switcher instead of two separate bottom-nav tabs.
class HourRequestsScreen extends ConsumerStatefulWidget {
  const HourRequestsScreen({super.key, this.initialTab = 0});

  /// 0 = external activities, 1 = credit transfers — lets callers (e.g. the
  /// dashboard's quick actions and feed rows) land on the relevant tab.
  final int initialTab;

  @override
  ConsumerState<HourRequestsScreen> createState() => _HourRequestsScreenState();
}

class _HourRequestsScreenState extends ConsumerState<HourRequestsScreen> {
  late int _tab = widget.initialTab;
  bool _showForm = false;

  static const _statusColors = {
    'pending': AppColors.statusPending,
    'approved': AppColors.statusApproved,
    'rejected': AppColors.statusRejected,
  };

  static const _statusLabels = {
    'pending': 'รอตรวจสอบ',
    'approved': 'อนุมัติแล้ว',
    'rejected': 'ถูกปฏิเสธ',
  };

  void _selectTab(int tab) {
    if (tab == _tab) return;
    setState(() {
      _tab = tab;
      _showForm = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    final isExternal = _tab == 0;
    final tokens = context.surfaceColors;

    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: RefreshIndicator(
          onRefresh: () => isExternal
              ? ref.refresh(externalActivitiesDataProvider.future)
              : ref.refresh(creditTransferRequestsProvider.future),
          child: ListView(
            padding: EdgeInsets.zero,
            children: [
              BrandHeader(
                title: isExternal ? 'กิจกรรมภายนอก' : 'เทียบโอนชั่วโมง',
                subtitle: isExternal
                    ? 'ส่งหลักฐานการเข้าร่วมกิจกรรมนอกมหาวิทยาลัย'
                    : 'สำหรับตำแหน่งผู้นำนักศึกษา',
                actions: [
                  IconButton(
                    icon: Icon(
                      _showForm ? Icons.close : Icons.add_circle,
                      color: Colors.white,
                    ),
                    tooltip: _showForm ? 'ยกเลิก' : 'เพิ่มคำร้อง',
                    onPressed: () => setState(() => _showForm = !_showForm),
                  ),
                ],
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
                child: Container(
                  padding: const EdgeInsets.all(4),
                  decoration: BoxDecoration(
                    color: tokens.surface,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: tokens.border),
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: _TabPill(
                          label: 'กิจกรรมภายนอก',
                          selected: isExternal,
                          onTap: () => _selectTab(0),
                        ),
                      ),
                      Expanded(
                        child: _TabPill(
                          label: 'เทียบโอนชั่วโมง',
                          selected: !isExternal,
                          onTap: () => _selectTab(1),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.all(20),
                child: isExternal ? _buildExternal() : _buildTransfers(),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildExternal() {
    final dataAsync = ref.watch(externalActivitiesDataProvider);
    final tokens = context.surfaceColors;

    return dataAsync.when(
      loading: () => const Padding(
        padding: EdgeInsets.only(top: 80),
        child: Center(child: CircularProgressIndicator()),
      ),
      error: (e, _) => const Padding(
        padding: EdgeInsets.only(top: 80),
        child: Center(child: Text('โหลดข้อมูลไม่สำเร็จ')),
      ),
      data: (data) => Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        spacing: 16,
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: AppColors.purple700.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Row(
              children: [
                Icon(Icons.info_outline, color: AppColors.purple700, size: 20),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    'ชั่วโมงคงเหลือปีการศึกษานี้: ${data.hoursRemaining} ชม.',
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ],
            ),
          ),
          if (_showForm)
            _ExternalActivityForm(
              onSubmitted: () => setState(() => _showForm = false),
            ),
          if (data.requests.isEmpty && !_showForm)
            Padding(
              padding: const EdgeInsets.only(top: 40),
              child: Center(
                child: Text(
                  'ยังไม่มีคำร้องกิจกรรมภายนอก',
                  style: TextStyle(color: tokens.textSecondary),
                ),
              ),
            ),
          ...data.requests.map(
            (r) => Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: tokens.surface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: tokens.border),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    margin: const EdgeInsets.only(top: 4),
                    width: 8,
                    height: 8,
                    decoration: BoxDecoration(
                      color: _statusColors[r.status] ?? Colors.grey,
                      shape: BoxShape.circle,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          r.title,
                          style: const TextStyle(
                            fontWeight: FontWeight.w700,
                            fontSize: 14,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          r.organization,
                          style: TextStyle(
                            fontSize: 12,
                            color: tokens.textSecondary,
                          ),
                        ),
                        const SizedBox(height: 6),
                        Text(
                          _statusLabels[r.status] ?? r.status,
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: _statusColors[r.status] ?? Colors.grey,
                          ),
                        ),
                        if (r.rejectReason != null) ...[
                          const SizedBox(height: 4),
                          Text(
                            r.rejectReason!,
                            style: TextStyle(
                              fontSize: 12,
                              color: tokens.textSecondary,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                  _HoursCell(
                    status: r.status,
                    requested: r.hoursRequested,
                    approved: r.hoursApproved,
                    credited: r.hoursCredited,
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTransfers() {
    final requestsAsync = ref.watch(creditTransferRequestsProvider);
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final tokens = context.surfaceColors;

    return requestsAsync.when(
      loading: () => const Padding(
        padding: EdgeInsets.only(top: 80),
        child: Center(child: CircularProgressIndicator()),
      ),
      error: (e, _) => const Padding(
        padding: EdgeInsets.only(top: 80),
        child: Center(child: Text('โหลดข้อมูลไม่สำเร็จ')),
      ),
      data: (requests) => Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        spacing: 16,
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: isDark
                  ? Colors.orange.shade900.withValues(alpha: 0.2)
                  : Colors.orange.shade50,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(
                color: isDark ? Colors.orange.shade800 : Colors.orange.shade100,
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              spacing: 8,
              children: [
                Row(
                  children: [
                    Icon(
                      Icons.warning_amber_rounded,
                      color: isDark
                          ? Colors.orange.shade400
                          : Colors.orange.shade700,
                      size: 20,
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'สำหรับผู้นำนักศึกษาเท่านั้น',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                          color: isDark
                              ? Colors.orange.shade300
                              : Colors.orange.shade800,
                        ),
                      ),
                    ),
                  ],
                ),
                Text(
                  'ใช้ได้เฉพาะตำแหน่งผู้นำนักศึกษาที่กำหนดเท่านั้น ได้แก่ '
                  'นายกองค์การบริหารนักศึกษา, นายกสโมสรนักศึกษา, ประธานสภานักศึกษา, '
                  'ประธานชมรม, ประธานหอพักมหาวิทยาลัย, หัวหน้าหมู่เรียน '
                  'และตัวแทนหมู่เรียน',
                  style: TextStyle(
                    fontSize: 12,
                    color: isDark
                        ? Colors.orange.shade200
                        : Colors.orange.shade900,
                    height: 1.4,
                  ),
                ),
                Text(
                  'ยื่นคำร้องได้เพียง 1 ครั้งต่อปีการศึกษาเท่านั้น',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: isDark
                        ? Colors.orange.shade200
                        : Colors.orange.shade900,
                  ),
                ),
              ],
            ),
          ),
          if (_showForm)
            _CreditTransferForm(
              onSubmitted: () => setState(() => _showForm = false),
            ),
          if (requests.isEmpty && !_showForm)
            Padding(
              padding: const EdgeInsets.only(top: 40),
              child: Center(
                child: Text(
                  'ยังไม่มีคำร้องเทียบโอนชั่วโมง',
                  style: TextStyle(color: tokens.textSecondary),
                ),
              ),
            ),
          ...requests.map(
            (r) => Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: tokens.surface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: tokens.border),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    margin: const EdgeInsets.only(top: 4),
                    width: 8,
                    height: 8,
                    decoration: BoxDecoration(
                      color: _statusColors[r.status] ?? Colors.grey,
                      shape: BoxShape.circle,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          r.positionLabel,
                          style: const TextStyle(
                            fontWeight: FontWeight.w700,
                            fontSize: 14,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          'ปีการศึกษา ${r.academicYear}',
                          style: TextStyle(
                            fontSize: 12,
                            color: tokens.textSecondary,
                          ),
                        ),
                        const SizedBox(height: 6),
                        Text(
                          _statusLabels[r.status] ?? r.status,
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: _statusColors[r.status] ?? Colors.grey,
                          ),
                        ),
                        if (r.rejectReason != null) ...[
                          const SizedBox(height: 4),
                          Text(
                            r.rejectReason!,
                            style: TextStyle(
                              fontSize: 12,
                              color: tokens.textSecondary,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                  _HoursCell(
                    status: r.status,
                    requested: r.hoursRequested,
                    approved: r.hoursApproved,
                    credited: r.hoursCredited,
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// Shows the credited hours; if an admin approved fewer hours than the
/// student requested, shows the original request struck through next to the
/// actual credit — matching the web hour-requests table.
class _HoursCell extends StatelessWidget {
  const _HoursCell({
    required this.status,
    required this.requested,
    required this.approved,
    required this.credited,
  });

  final String status;
  final int requested;
  final int? approved;
  final int credited;

  @override
  Widget build(BuildContext context) {
    final isPartial =
        status == 'approved' && approved != null && approved != requested;

    if (!isPartial) {
      return Text(
        '$credited ชม.',
        style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700),
      );
    }

    return RichText(
      textAlign: TextAlign.right,
      text: TextSpan(
        style: DefaultTextStyle.of(context).style,
        children: [
          TextSpan(
            text: '$requested ',
            style: TextStyle(
              fontSize: 12,
              color: context.surfaceColors.textSecondary,
              decoration: TextDecoration.lineThrough,
            ),
          ),
          TextSpan(
            text: '$credited ชม.',
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w700,
              color: AppColors.green600,
            ),
          ),
        ],
      ),
    );
  }
}

class _TabPill extends StatelessWidget {
  const _TabPill({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(
          color: selected ? AppColors.purple700 : Colors.transparent,
          borderRadius: BorderRadius.circular(10),
        ),
        child: Text(
          label,
          textAlign: TextAlign.center,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w600,
            color: selected
                ? Colors.white
                : context.surfaceColors.textSecondary,
          ),
        ),
      ),
    );
  }
}

class _ExternalActivityForm extends ConsumerStatefulWidget {
  const _ExternalActivityForm({required this.onSubmitted});

  final VoidCallback onSubmitted;

  @override
  ConsumerState<_ExternalActivityForm> createState() =>
      _ExternalActivityFormState();
}

class _ExternalActivityFormState extends ConsumerState<_ExternalActivityForm> {
  final _titleController = TextEditingController();
  final _organizationController = TextEditingController();
  final _hoursController = TextEditingController();
  final DateTime _activityDate = DateTime.now();
  String _category = 'volunteer';
  String? _photoPath;
  bool _submitting = false;
  String? _errorMessage;

  static const _categories = {
    'culture': 'ทำนุบำรุงศิลปวัฒนธรรม',
    'academic': 'วิชาการ',
    'sports': 'กีฬาและส่งเสริมสุขภาพ',
    'volunteer': 'จิตอาสา/บำเพ็ญประโยชน์',
    'ethics': 'คุณธรรมจริยธรรม',
  };

  Future<void> _pickPhoto() async {
    final photo = await ImagePicker().pickImage(
      source: ImageSource.camera,
      imageQuality: 80,
      maxWidth: 1280,
    );
    if (photo != null) setState(() => _photoPath = photo.path);
  }

  Future<void> _submit() async {
    final hours = int.tryParse(_hoursController.text.trim());
    if (_titleController.text.trim().isEmpty ||
        _organizationController.text.trim().isEmpty ||
        hours == null ||
        _photoPath == null) {
      setState(() => _errorMessage = 'กรุณากรอกข้อมูลให้ครบถ้วน');
      return;
    }

    setState(() {
      _submitting = true;
      _errorMessage = null;
    });

    try {
      await ref
          .read(externalActivitiesRepositoryProvider)
          .submit(
            title: _titleController.text.trim(),
            organization: _organizationController.text.trim(),
            activityDate:
                '${_activityDate.year}-${_activityDate.month.toString().padLeft(2, '0')}-${_activityDate.day.toString().padLeft(2, '0')}',
            activityCategory: _category,
            hoursRequested: hours,
            proofImagePath: _photoPath!,
          );

      ref.invalidate(externalActivitiesDataProvider);
      widget.onSubmitted();
    } on ApiException catch (e) {
      setState(() => _errorMessage = e.message);
    } catch (_) {
      setState(() => _errorMessage = 'ส่งคำร้องไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return SectionCard(
      icon: Icons.groups_outlined,
      title: 'ส่งคำร้องกิจกรรมภายนอก',
      children: [
        TextField(
          controller: _titleController,
          decoration: const InputDecoration(labelText: 'ชื่อกิจกรรม'),
        ),
        TextField(
          controller: _organizationController,
          decoration: const InputDecoration(labelText: 'หน่วยงาน'),
        ),
        TextField(
          controller: _hoursController,
          keyboardType: TextInputType.number,
          decoration: const InputDecoration(labelText: 'จำนวนชั่วโมง'),
        ),
        DropdownButtonFormField<String>(
          initialValue: _category,
          isExpanded: true,
          decoration: const InputDecoration(labelText: 'หมวดหมู่'),
          items: _categories.entries
              .map(
                (e) => DropdownMenuItem(
                  value: e.key,
                  child: Text(e.value, overflow: TextOverflow.ellipsis),
                ),
              )
              .toList(),
          onChanged: (v) => setState(() => _category = v!),
        ),
        if (_photoPath != null)
          ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: Image.file(
              File(_photoPath!),
              height: 150,
              width: double.infinity,
              fit: BoxFit.cover,
            ),
          ),
        OutlinedButton.icon(
          onPressed: _pickPhoto,
          icon: const Icon(Icons.camera_alt_outlined),
          label: Text(_photoPath == null ? 'แนบหลักฐาน' : 'ถ่ายใหม่'),
        ),
        if (_errorMessage != null)
          Text(
            _errorMessage!,
            style: const TextStyle(color: Colors.red, fontSize: 13),
          ),
        FilledButton.icon(
          onPressed: _submitting ? null : _submit,
          icon: _submitting
              ? const SizedBox(
                  width: 16,
                  height: 16,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    color: Colors.white,
                  ),
                )
              : const Icon(Icons.send_outlined),
          label: Text(_submitting ? 'กำลังส่ง...' : 'ส่งคำร้อง'),
        ),
      ],
    );
  }
}

class _CreditTransferForm extends ConsumerStatefulWidget {
  const _CreditTransferForm({required this.onSubmitted});

  final VoidCallback onSubmitted;

  @override
  ConsumerState<_CreditTransferForm> createState() =>
      _CreditTransferFormState();
}

class _CreditTransferFormState extends ConsumerState<_CreditTransferForm> {
  CreditTransferPosition? _position;
  int _academicYear = DateTime.now().year + 543;
  String? _photoPath;
  bool _submitting = false;
  String? _errorMessage;

  Future<void> _pickPhoto() async {
    final photo = await ImagePicker().pickImage(
      source: ImageSource.camera,
      imageQuality: 80,
      maxWidth: 1280,
    );
    if (photo != null) setState(() => _photoPath = photo.path);
  }

  Future<void> _submit() async {
    if (_position == null || _photoPath == null) {
      setState(() => _errorMessage = 'กรุณากรอกข้อมูลให้ครบถ้วน');
      return;
    }

    setState(() {
      _submitting = true;
      _errorMessage = null;
    });

    try {
      await ref
          .read(creditTransfersRepositoryProvider)
          .submit(
            position: _position!.key,
            academicYear: _academicYear,
            proofImagePath: _photoPath!,
          );

      ref.invalidate(creditTransferRequestsProvider);
      widget.onSubmitted();
    } on ApiException catch (e) {
      setState(() => _errorMessage = e.message);
    } catch (_) {
      setState(() => _errorMessage = 'ส่งคำร้องไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final positionsAsync = ref.watch(creditTransferPositionsProvider);

    return SectionCard(
      icon: Icons.swap_horiz,
      title: 'ส่งคำร้องเทียบโอนชั่วโมง',
      children: [
        positionsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => const Text('โหลดตำแหน่งไม่สำเร็จ'),
          data: (positions) => DropdownButtonFormField<CreditTransferPosition>(
            initialValue: _position,
            isExpanded: true,
            decoration: const InputDecoration(labelText: 'ตำแหน่ง'),
            items: positions
                .map(
                  (p) => DropdownMenuItem(
                    value: p,
                    child: Text(
                      '${p.label} (${p.hours} ชม.)',
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                )
                .toList(),
            onChanged: (v) => setState(() => _position = v),
          ),
        ),
        TextFormField(
          initialValue: _academicYear.toString(),
          decoration: const InputDecoration(labelText: 'ปีการศึกษา (พ.ศ.)'),
          keyboardType: TextInputType.number,
          onChanged: (v) => _academicYear = int.tryParse(v) ?? _academicYear,
        ),
        if (_photoPath != null)
          ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: Image.file(
              File(_photoPath!),
              height: 150,
              width: double.infinity,
              fit: BoxFit.cover,
            ),
          ),
        OutlinedButton.icon(
          onPressed: _pickPhoto,
          icon: const Icon(Icons.camera_alt_outlined),
          label: Text(_photoPath == null ? 'แนบหลักฐาน' : 'ถ่ายใหม่'),
        ),
        if (_errorMessage != null)
          Text(
            _errorMessage!,
            style: const TextStyle(color: Colors.red, fontSize: 13),
          ),
        FilledButton.icon(
          onPressed: _submitting ? null : _submit,
          icon: _submitting
              ? const SizedBox(
                  width: 16,
                  height: 16,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    color: Colors.white,
                  ),
                )
              : const Icon(Icons.send_outlined),
          label: Text(_submitting ? 'กำลังส่ง...' : 'ส่งคำร้อง'),
        ),
      ],
    );
  }
}
