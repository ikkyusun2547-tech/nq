import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';

import '../../core/api_exception.dart';
import '../../core/models/activity.dart';
import '../../core/providers.dart';
import '../../core/theme.dart';
import '../self_checkin/self_checkin_screen.dart' show DottedBorderBox;
import 'late_checkin_repository.dart';

class LateCheckInScreen extends ConsumerStatefulWidget {
  const LateCheckInScreen({super.key, required this.activity});

  final Activity activity;

  @override
  ConsumerState<LateCheckInScreen> createState() => _LateCheckInScreenState();
}

class _LateCheckInScreenState extends ConsumerState<LateCheckInScreen> {
  final _reasonController = TextEditingController();
  String? _photoPath;
  bool _loading = true;
  bool _submitting = false;
  String? _errorMessage;
  String? _successMessage;
  LateCheckInRequestInfo? _existing;

  @override
  void initState() {
    super.initState();
    _loadExisting();
  }

  Future<void> _loadExisting() async {
    try {
      final existing = await ref
          .read(lateCheckInRepositoryProvider)
          .fetchExisting(widget.activity.id);
      setState(() {
        _existing = existing;
        _loading = false;
      });
    } catch (_) {
      setState(() => _loading = false);
    }
  }

  Future<void> _pickPhoto() async {
    final photo = await ImagePicker().pickImage(
      source: ImageSource.camera,
      imageQuality: 80,
      maxWidth: 1280,
    );
    if (photo != null) {
      setState(() => _photoPath = photo.path);
    }
  }

  Future<void> _submit() async {
    if (_photoPath == null || _reasonController.text.trim().isEmpty) {
      setState(() => _errorMessage = 'กรุณากรอกเหตุผลและแนบหลักฐาน');
      return;
    }

    setState(() {
      _submitting = true;
      _errorMessage = null;
    });

    try {
      final message = await ref
          .read(lateCheckInRepositoryProvider)
          .submit(
            activityId: widget.activity.id,
            reason: _reasonController.text.trim(),
            proofImagePath: _photoPath!,
          );
      setState(() => _successMessage = message);
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
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      appBar: AppBar(title: Text(widget.activity.title)),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: _successMessage != null
                  ? _buildCard(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Container(
                            width: 72,
                            height: 72,
                            decoration: BoxDecoration(
                              color: isDark
                                  ? AppColors.green700.withValues(alpha: 0.3)
                                  : AppColors.green50,
                              shape: BoxShape.circle,
                            ),
                            child: Icon(
                              Icons.check_circle,
                              size: 40,
                              color: AppColors.green600,
                            ),
                          ),
                          const SizedBox(height: 16),
                          Text(_successMessage!, textAlign: TextAlign.center),
                          const SizedBox(height: 24),
                          SizedBox(
                            width: double.infinity,
                            child: FilledButton(
                              onPressed: () => Navigator.of(context).pop(true),
                              child: const Text('เสร็จสิ้น'),
                            ),
                          ),
                        ],
                      ),
                    )
                  : _existing != null
                  ? _buildCard(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            Icons.hourglass_top,
                            size: 40,
                            color: AppColors.statusPending,
                          ),
                          const SizedBox(height: 16),
                          Text(
                            'คุณมีคำร้องขอเช็คชื่อย้อนหลังอยู่แล้ว\nสถานะ: ${_existing!.status}'
                            '${_existing!.rejectReason != null ? '\nเหตุผล: ${_existing!.rejectReason}' : ''}',
                            textAlign: TextAlign.center,
                          ),
                        ],
                      ),
                    )
                  : _buildCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        spacing: 16,
                        children: [
                          Row(
                            children: [
                              Container(
                                width: 44,
                                height: 44,
                                decoration: BoxDecoration(
                                  color: isDark
                                      ? AppColors.purple900.withValues(
                                          alpha: 0.4,
                                        )
                                      : AppColors.purple50,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Icon(
                                  Icons.history,
                                  color: isDark
                                      ? AppColors.purple400
                                      : AppColors.purple700,
                                ),
                              ),
                              const SizedBox(width: 12),
                              const Expanded(
                                child: Text(
                                  'ขอเช็คชื่อย้อนหลัง',
                                  style: TextStyle(
                                    fontWeight: FontWeight.w700,
                                    fontSize: 15,
                                  ),
                                ),
                              ),
                            ],
                          ),
                          TextField(
                            controller: _reasonController,
                            decoration: const InputDecoration(
                              labelText: 'เหตุผลที่ขอเช็คชื่อย้อนหลัง',
                            ),
                            maxLines: 3,
                          ),
                          if (_photoPath != null)
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.stretch,
                              children: [
                                ClipRRect(
                                  borderRadius: BorderRadius.circular(14),
                                  child: Image.file(
                                    File(_photoPath!),
                                    height: 200,
                                    fit: BoxFit.cover,
                                  ),
                                ),
                                const SizedBox(height: 10),
                                OutlinedButton.icon(
                                  onPressed: _pickPhoto,
                                  icon: const Icon(Icons.refresh),
                                  label: const Text('ถ่ายใหม่'),
                                ),
                              ],
                            )
                          else
                            InkWell(
                              onTap: _pickPhoto,
                              borderRadius: BorderRadius.circular(14),
                              child: DottedBorderBox(
                                child: Column(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Icon(
                                      Icons.add_a_photo_outlined,
                                      size: 32,
                                      color: AppColors.purple400,
                                    ),
                                    const SizedBox(height: 8),
                                    Text(
                                      'แตะเพื่อแนบหลักฐาน',
                                      style: TextStyle(
                                        color: AppColors.purple700,
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          if (_errorMessage != null)
                            Text(
                              _errorMessage!,
                              style: const TextStyle(
                                color: Colors.red,
                                fontSize: 13,
                              ),
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
                            label: Text(
                              _submitting ? 'กำลังส่ง...' : 'ส่งคำร้อง',
                            ),
                          ),
                        ],
                      ),
                    ),
            ),
    );
  }

  Widget _buildCard({required Widget child}) {
    final tokens = context.surfaceColors;

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: tokens.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: tokens.border),
      ),
      child: child,
    );
  }
}
