import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import 'package:image_picker/image_picker.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../../core/api_exception.dart';
import '../../core/models/activity.dart';
import '../../core/providers.dart';
import '../../core/theme.dart';
import 'checkin_repository.dart';

enum _Step { scan, selfie, location, submitting, done }

/// Mirrors resources/views/student/checkin.blade.php's Alpine checkinApp():
/// scan QR -> selfie via camera -> GPS fix (with a manual watchdog, kept
/// even though native geolocation is more reliable than the web app's
/// Chrome-iOS workaround was — cheap insurance) -> submit multipart.
class CheckInFlowScreen extends ConsumerStatefulWidget {
  const CheckInFlowScreen({super.key, this.activity});

  /// Null when entered from the dashboard's generic "สแกน QR เช็คชื่อ" quick
  /// action (mirrors the web's activity-less `/checkin` route) — the scanned
  /// QR token alone resolves the activity server-side, this is only used to
  /// title the app bar.
  final Activity? activity;

  @override
  ConsumerState<CheckInFlowScreen> createState() => _CheckInFlowScreenState();
}

class _CheckInFlowScreenState extends ConsumerState<CheckInFlowScreen> {
  _Step _step = _Step.scan;
  String? _qrToken;
  String? _photoPath;
  Position? _position;
  String? _errorMessage;
  CheckInResult? _result;

  Future<void> _onQrDetected(String token) async {
    if (_qrToken != null) return;
    setState(() {
      _qrToken = token;
      _step = _Step.selfie;
    });
  }

  Future<void> _takeSelfie() async {
    final photo = await ImagePicker().pickImage(
      source: ImageSource.camera,
      preferredCameraDevice: CameraDevice.front,
      imageQuality: 80,
      maxWidth: 1280,
    );

    if (photo == null) return;

    setState(() {
      _photoPath = photo.path;
      _step = _Step.location;
    });
    _fetchLocation();
  }

  Future<void> _fetchLocation() async {
    setState(() => _errorMessage = null);

    try {
      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        setState(
          () => _errorMessage = 'กรุณาอนุญาตการเข้าถึงตำแหน่งเพื่อเช็คชื่อ',
        );
        return;
      }

      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
        ),
      ).timeout(const Duration(seconds: 12));

      setState(() => _position = position);
      await _submit();
    } on TimeoutException {
      setState(
        () => _errorMessage = 'ค้นหาตำแหน่งไม่สำเร็จ กรุณาลองใหม่อีกครั้ง',
      );
    } catch (_) {
      setState(
        () => _errorMessage = 'ค้นหาตำแหน่งไม่สำเร็จ กรุณาลองใหม่อีกครั้ง',
      );
    }
  }

  Future<void> _submit() async {
    if (_qrToken == null || _photoPath == null || _position == null) return;

    setState(() {
      _step = _Step.submitting;
      _errorMessage = null;
    });

    try {
      final deviceUuid = await ref
          .read(authStorageProvider)
          .getOrCreateDeviceUuid();
      final result = await ref
          .read(checkInRepositoryProvider)
          .submit(
            qrToken: _qrToken!,
            lat: _position!.latitude,
            lng: _position!.longitude,
            deviceUuid: deviceUuid,
            photoPath: _photoPath!,
          );

      setState(() {
        _result = result;
        _step = _Step.done;
      });
    } on ApiException catch (e) {
      setState(() {
        _errorMessage = e.message;
        _step = _Step.location;
      });
    } catch (_) {
      setState(() {
        _errorMessage = 'เช็คชื่อไม่สำเร็จ กรุณาลองใหม่อีกครั้ง';
        _step = _Step.location;
      });
    }
  }

  // Matches resources/views/student/checkin.blade.php's 3-step indicator —
  // location/submit/done collapse into the same "ยืนยัน" stage there.
  static const _stepLabels = ['สแกน QR', 'ถ่ายเซลฟี', 'ยืนยัน'];

  int get _stepIndex => switch (_step) {
    _Step.scan => 0,
    _Step.selfie => 1,
    _Step.location || _Step.submitting || _Step.done => 2,
  };

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.activity?.title ?? 'สแกน QR เช็คชื่อ'),
      ),
      body: Column(
        children: [
          _StepIndicator(labels: _stepLabels, currentIndex: _stepIndex),
          Expanded(
            child: switch (_step) {
              _Step.scan => ClipRRect(
                borderRadius: BorderRadius.circular(20),
                child: Container(
                  margin: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                  clipBehavior: Clip.antiAlias,
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: MobileScanner(
                    onDetect: (capture) {
                      final token = capture.barcodes.isNotEmpty
                          ? capture.barcodes.first.rawValue
                          : null;
                      if (token != null) _onQrDetected(token);
                    },
                  ),
                ),
              ),
              _Step.selfie => _buildActionStep(
                icon: Icons.camera_alt_outlined,
                label: 'ถ่ายเซลฟีเพื่อยืนยันตัวตน',
                onPressed: _takeSelfie,
              ),
              _Step.location => _buildLocationStep(),
              _Step.submitting => const Center(
                child: CircularProgressIndicator(),
              ),
              _Step.done => _buildDoneStep(),
            },
          ),
        ],
      ),
    );
  }

  Widget _buildActionStep({
    required IconData icon,
    required String label,
    required VoidCallback onPressed,
  }) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Center(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: _StepCard(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 72,
                height: 72,
                decoration: BoxDecoration(
                  color: isDark
                      ? AppColors.purple900.withValues(alpha: 0.4)
                      : AppColors.purple50,
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  icon,
                  size: 34,
                  color: isDark ? AppColors.purple400 : AppColors.purple700,
                ),
              ),
              const SizedBox(height: 20),
              Text(
                label,
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 15),
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: FilledButton(
                  onPressed: onPressed,
                  child: const Text('ดำเนินการต่อ'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildLocationStep() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: _StepCard(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              if (_errorMessage != null) ...[
                Icon(
                  Icons.location_off_outlined,
                  size: 40,
                  color: AppColors.statusRejected,
                ),
                const SizedBox(height: 12),
                Text(
                  _errorMessage!,
                  style: const TextStyle(color: Colors.red),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton(
                    onPressed: _fetchLocation,
                    child: const Text('ลองอีกครั้ง'),
                  ),
                ),
              ] else ...[
                const CircularProgressIndicator(),
                const SizedBox(height: 16),
                const Text('กำลังค้นหาตำแหน่งของคุณ...'),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDoneStep() {
    final result = _result!;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Center(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: _StepCard(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 80,
                height: 80,
                decoration: BoxDecoration(
                  color: result.autoApproved
                      ? (isDark
                            ? AppColors.green700.withValues(alpha: 0.3)
                            : AppColors.green50)
                      : (isDark
                            ? Colors.orange.shade900.withValues(alpha: 0.3)
                            : Colors.orange.shade50),
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  result.autoApproved
                      ? Icons.check_circle
                      : Icons.warning_amber,
                  size: 44,
                  color: result.autoApproved
                      ? AppColors.green600
                      : (isDark
                            ? Colors.orange.shade400
                            : Colors.orange.shade700),
                ),
              ),
              const SizedBox(height: 20),
              Text(
                result.message,
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 15),
              ),
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
        ),
      ),
    );
  }
}

class _StepCard extends StatelessWidget {
  const _StepCard({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    final tokens = context.surfaceColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Container(
      width: double.infinity,
      constraints: const BoxConstraints(maxWidth: 360),
      padding: const EdgeInsets.all(28),
      decoration: BoxDecoration(
        color: tokens.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: tokens.border),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDark ? 0.25 : 0.04),
            blurRadius: 16,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: child,
    );
  }
}

class _StepIndicator extends StatelessWidget {
  const _StepIndicator({required this.labels, required this.currentIndex});

  final List<String> labels;
  final int currentIndex;

  @override
  Widget build(BuildContext context) {
    final tokens = context.surfaceColors;

    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 16),
      child: Row(
        children: List.generate(labels.length * 2 - 1, (i) {
          if (i.isOdd) {
            final done = (i ~/ 2) < currentIndex;
            return Expanded(
              child: Container(
                height: 2,
                color: done ? AppColors.purple700 : tokens.border,
              ),
            );
          }

          final index = i ~/ 2;
          final active = index == currentIndex;
          final done = index < currentIndex;

          return Column(
            children: [
              Container(
                width: 28,
                height: 28,
                decoration: BoxDecoration(
                  color: (active || done)
                      ? AppColors.purple700
                      : tokens.surface,
                  shape: BoxShape.circle,
                  border: Border.all(
                    color: (active || done)
                        ? AppColors.purple700
                        : tokens.border,
                  ),
                ),
                child: Center(
                  child: done
                      ? const Icon(Icons.check, size: 16, color: Colors.white)
                      : Text(
                          '${index + 1}',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                            color: active ? Colors.white : tokens.textSecondary,
                          ),
                        ),
                ),
              ),
            ],
          );
        }),
      ),
    );
  }
}
