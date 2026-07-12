import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api_exception.dart';
import '../../core/models/faculty.dart';
import '../../core/providers.dart';
import '../../core/theme.dart';
import '../../core/widgets/section_card.dart';
import '../auth/auth_controller.dart';

class ProfileSetupScreen extends ConsumerStatefulWidget {
  const ProfileSetupScreen({super.key});

  @override
  ConsumerState<ProfileSetupScreen> createState() => _ProfileSetupScreenState();
}

class _ProfileSetupScreenState extends ConsumerState<ProfileSetupScreen> {
  final _formKey = GlobalKey<FormState>();
  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _studentIdController = TextEditingController();
  final _enrollmentYearController = TextEditingController();

  String _titlePrefix = 'นาย';
  int? _yearLevel;
  String _programType = 'normal';
  Faculty? _faculty;
  int? _majorId;

  List<Faculty> _faculties = [];
  bool _loading = true;
  bool _submitting = false;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _loadFaculties();
  }

  Future<void> _loadFaculties() async {
    try {
      final faculties = await ref
          .read(profileSetupRepositoryProvider)
          .fetchFaculties();
      setState(() {
        _faculties = faculties;
        _loading = false;
      });
    } catch (_) {
      setState(() {
        _errorMessage = 'โหลดข้อมูลคณะ/สาขาไม่สำเร็จ';
        _loading = false;
      });
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate() ||
        _faculty == null ||
        _majorId == null ||
        _yearLevel == null) {
      setState(() => _errorMessage = 'กรุณากรอกข้อมูลให้ครบถ้วน');
      return;
    }

    setState(() {
      _submitting = true;
      _errorMessage = null;
    });

    try {
      await ref
          .read(profileSetupRepositoryProvider)
          .submit(
            titlePrefix: _titlePrefix,
            firstName: _firstNameController.text.trim(),
            lastName: _lastNameController.text.trim(),
            studentId: _studentIdController.text.trim(),
            enrollmentYear: int.parse(_enrollmentYearController.text.trim()),
            yearLevel: _yearLevel!,
            programType: _programType,
            facultyId: _faculty!.id,
            majorId: _majorId!,
          );

      await ref.read(authControllerProvider.notifier).refreshProfile();
    } on DioException catch (e) {
      final apiEx = e.asApiException;
      setState(
        () => _errorMessage =
            apiEx.errors?.values.firstOrNull?.first?.toString() ??
            apiEx.message,
      );
    } catch (_) {
      setState(
        () => _errorMessage = 'บันทึกข้อมูลไม่สำเร็จ กรุณาลองใหม่อีกครั้ง',
      );
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  InputDecoration _decoration(String label, {IconData? icon}) {
    return InputDecoration(
      labelText: label,
      prefixIcon: icon != null ? Icon(icon, size: 20) : null,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              BrandHeader(
                title: 'ตั้งค่าโปรไฟล์',
                subtitle: 'กรอกข้อมูลให้ครบถ้วนก่อนเริ่มใช้งาน',
                actions: [
                  IconButton(
                    icon: const Icon(Icons.logout, color: Colors.white),
                    tooltip: 'ออกจากระบบ',
                    onPressed: () =>
                        ref.read(authControllerProvider.notifier).logout(),
                  ),
                ],
              ),
              if (_loading)
                const Padding(
                  padding: EdgeInsets.only(top: 80),
                  child: Center(child: CircularProgressIndicator()),
                )
              else
                Padding(
                  padding: const EdgeInsets.all(20),
                  child: Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      spacing: 16,
                      children: [
                        if (_errorMessage != null)
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 16,
                              vertical: 12,
                            ),
                            decoration: BoxDecoration(
                              color: AppColors.statusRejected.withValues(
                                alpha: 0.08,
                              ),
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(
                                color: AppColors.statusRejected.withValues(
                                  alpha: 0.2,
                                ),
                              ),
                            ),
                            child: Text(
                              _errorMessage!,
                              style: const TextStyle(
                                color: AppColors.statusRejected,
                                fontSize: 13,
                              ),
                            ),
                          ),
                        SectionCard(
                          icon: Icons.badge_outlined,
                          title: 'ข้อมูลส่วนตัว',
                          children: [
                            DropdownButtonFormField<String>(
                              initialValue: _titlePrefix,
                              isExpanded: true,
                              decoration: _decoration(
                                'คำนำหน้า',
                                icon: Icons.person_outline,
                              ),
                              items: const [
                                DropdownMenuItem(
                                  value: 'นาย',
                                  child: Text('นาย'),
                                ),
                                DropdownMenuItem(
                                  value: 'นาง',
                                  child: Text('นาง'),
                                ),
                                DropdownMenuItem(
                                  value: 'นางสาว',
                                  child: Text('นางสาว'),
                                ),
                              ],
                              onChanged: (v) =>
                                  setState(() => _titlePrefix = v!),
                            ),
                            TextFormField(
                              controller: _firstNameController,
                              decoration: _decoration('ชื่อ'),
                              validator: (v) => (v == null || v.trim().isEmpty)
                                  ? 'กรุณากรอกชื่อ'
                                  : null,
                            ),
                            TextFormField(
                              controller: _lastNameController,
                              decoration: _decoration('นามสกุล'),
                              validator: (v) => (v == null || v.trim().isEmpty)
                                  ? 'กรุณากรอกนามสกุล'
                                  : null,
                            ),
                            TextFormField(
                              controller: _studentIdController,
                              decoration: _decoration('รหัสนักศึกษา (11 หลัก)'),
                              keyboardType: TextInputType.number,
                              validator: (v) =>
                                  (v == null || v.trim().length != 11)
                                  ? 'รหัสนักศึกษาต้องมี 11 หลัก'
                                  : null,
                            ),
                          ],
                        ),
                        SectionCard(
                          icon: Icons.school_outlined,
                          title: 'ข้อมูลการศึกษา',
                          children: [
                            TextFormField(
                              controller: _enrollmentYearController,
                              decoration: _decoration(
                                'ปีที่เข้าศึกษา (พ.ศ.)',
                                icon: Icons.calendar_today_outlined,
                              ),
                              keyboardType: TextInputType.number,
                              validator: (v) =>
                                  (v == null || int.tryParse(v) == null)
                                  ? 'กรุณากรอกปี พ.ศ.'
                                  : null,
                            ),
                            DropdownButtonFormField<int>(
                              initialValue: _yearLevel,
                              isExpanded: true,
                              decoration: _decoration('ชั้นปี'),
                              items: const [1, 2, 3, 4]
                                  .map(
                                    (y) => DropdownMenuItem(
                                      value: y,
                                      child: Text('ปี $y'),
                                    ),
                                  )
                                  .toList(),
                              onChanged: (v) => setState(() => _yearLevel = v),
                            ),
                            DropdownButtonFormField<String>(
                              initialValue: _programType,
                              isExpanded: true,
                              decoration: _decoration('ประเภทหลักสูตร'),
                              items: const [
                                DropdownMenuItem(
                                  value: 'normal',
                                  child: Text('ปกติ'),
                                ),
                                DropdownMenuItem(
                                  value: 'special',
                                  child: Text('พิเศษ (เทียบโอน)'),
                                ),
                              ],
                              onChanged: (v) =>
                                  setState(() => _programType = v!),
                            ),
                            DropdownButtonFormField<Faculty>(
                              initialValue: _faculty,
                              isExpanded: true,
                              decoration: _decoration('คณะ'),
                              items: _faculties
                                  .map(
                                    (f) => DropdownMenuItem(
                                      value: f,
                                      child: Text(
                                        f.nameTh,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                  )
                                  .toList(),
                              onChanged: (v) => setState(() {
                                _faculty = v;
                                _majorId = null;
                              }),
                            ),
                            DropdownButtonFormField<int>(
                              initialValue: _majorId,
                              isExpanded: true,
                              decoration: _decoration('สาขา'),
                              items: (_faculty?.majors ?? [])
                                  .map(
                                    (m) => DropdownMenuItem(
                                      value: m.id,
                                      child: Text(
                                        m.nameTh,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                  )
                                  .toList(),
                              onChanged: _faculty == null
                                  ? null
                                  : (v) => setState(() => _majorId = v),
                            ),
                          ],
                        ),
                        const SizedBox(height: 4),
                        SizedBox(
                          height: 52,
                          child: FilledButton.icon(
                            onPressed: _submitting ? null : _submit,
                            icon: _submitting
                                ? const SizedBox(
                                    width: 18,
                                    height: 18,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: Colors.white,
                                    ),
                                  )
                                : const Icon(Icons.check_circle_outline),
                            label: Text(
                              _submitting ? 'กำลังบันทึก...' : 'บันทึกข้อมูล',
                            ),
                          ),
                        ),
                      ],
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
