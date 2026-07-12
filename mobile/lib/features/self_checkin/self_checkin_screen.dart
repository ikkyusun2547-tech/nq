import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';

import '../../core/api_exception.dart';
import '../../core/models/activity.dart';
import '../../core/providers.dart';
import '../../core/theme.dart';

class SelfCheckInScreen extends ConsumerStatefulWidget {
  const SelfCheckInScreen({super.key, required this.activity});

  final Activity activity;

  @override
  ConsumerState<SelfCheckInScreen> createState() => _SelfCheckInScreenState();
}

class _SelfCheckInScreenState extends ConsumerState<SelfCheckInScreen> {
  String? _photoPath;
  bool _submitting = false;
  String? _errorMessage;
  String? _successMessage;

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
    if (_photoPath == null) return;

    setState(() {
      _submitting = true;
      _errorMessage = null;
    });

    try {
      final message = await ref
          .read(selfCheckInRepositoryProvider)
          .submit(activityId: widget.activity.id, photoPath: _photoPath!);
      setState(() => _successMessage = message);
    } on DioException catch (e) {
      setState(() => _errorMessage = e.asApiException.message);
    } catch (_) {
      setState(() => _errorMessage = 'ส่งข้อมูลไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final tokens = context.surfaceColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      appBar: AppBar(title: Text(widget.activity.title)),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: _successMessage != null
            ? _SuccessCard(
                message: _successMessage!,
                onDone: () => Navigator.of(context).pop(true),
              )
            : Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: tokens.surface,
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: tokens.border),
                ),
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
                                ? AppColors.purple900.withValues(alpha: 0.4)
                                : AppColors.purple50,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Icon(
                            Icons.camera_alt_outlined,
                            color: isDark
                                ? AppColors.purple400
                                : AppColors.purple700,
                          ),
                        ),
                        const SizedBox(width: 12),
                        const Expanded(
                          child: Text(
                            'ถ่ายภาพหลักฐานการเข้าร่วมกิจกรรม',
                            style: TextStyle(
                              fontWeight: FontWeight.w700,
                              fontSize: 15,
                            ),
                          ),
                        ),
                      ],
                    ),
                    _PhotoPicker(photoPath: _photoPath, onPick: _pickPhoto),
                    if (_errorMessage != null)
                      Text(
                        _errorMessage!,
                        style: const TextStyle(
                          color: AppColors.statusRejected,
                          fontSize: 13,
                        ),
                      ),
                    FilledButton.icon(
                      onPressed: (_photoPath == null || _submitting)
                          ? null
                          : _submit,
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
                      label: Text(_submitting ? 'กำลังส่ง...' : 'ส่งหลักฐาน'),
                    ),
                  ],
                ),
              ),
      ),
    );
  }
}

/// Shared dashed-style photo picker used by self-checkin and late-checkin.
class _PhotoPicker extends StatelessWidget {
  const _PhotoPicker({required this.photoPath, required this.onPick});

  final String? photoPath;
  final VoidCallback onPick;

  @override
  Widget build(BuildContext context) {
    if (photoPath != null) {
      return Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(14),
            child: Image.file(File(photoPath!), height: 220, fit: BoxFit.cover),
          ),
          const SizedBox(height: 10),
          OutlinedButton.icon(
            onPressed: onPick,
            icon: const Icon(Icons.refresh),
            label: const Text('ถ่ายใหม่'),
          ),
        ],
      );
    }

    return InkWell(
      onTap: onPick,
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
              'แตะเพื่อถ่ายภาพ',
              style: TextStyle(
                color: Theme.of(context).brightness == Brightness.dark
                    ? AppColors.purple400
                    : AppColors.purple700,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class DottedBorderBox extends StatelessWidget {
  const DottedBorderBox({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Container(
      width: double.infinity,
      height: 160,
      decoration: BoxDecoration(
        color: isDark
            ? AppColors.purple900.withValues(alpha: 0.25)
            : AppColors.purple50,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isDark ? AppColors.darkBorder : AppColors.purple200,
          width: 1.5,
        ),
      ),
      child: Center(child: child),
    );
  }
}

class _SuccessCard extends StatelessWidget {
  const _SuccessCard({required this.message, required this.onDone});

  final String message;
  final VoidCallback onDone;

  @override
  Widget build(BuildContext context) {
    final tokens = context.surfaceColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Container(
      padding: const EdgeInsets.all(28),
      decoration: BoxDecoration(
        color: tokens.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: tokens.border),
      ),
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
          Text(
            message,
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 14),
          ),
          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity,
            child: FilledButton(
              onPressed: onDone,
              child: const Text('เสร็จสิ้น'),
            ),
          ),
        ],
      ),
    );
  }
}
