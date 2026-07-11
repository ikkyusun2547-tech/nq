import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api_exception.dart';
import '../../core/models/app_user.dart';
import '../../core/models/faculty.dart';
import '../../core/providers.dart';
import '../../core/theme.dart';
import '../../core/widgets/section_card.dart';
import '../auth/auth_controller.dart';

/// Same fields/endpoint as onboarding's ProfileSetupScreen (POST /setup-profile
/// is an unconditional `$user->update(...)`, not a create-once), just
/// pre-filled with the signed-in user's current data so it reads as "edit"
/// rather than "first-time setup".
class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key});

  @override
  ConsumerState<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends ConsumerState<ProfileScreen> {
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
  bool _editing = false;
  String? _errorMessage;
  String? _successMessage;

  static const _titlePrefixes = ['นางสาว', 'นาย', 'นาง'];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final faculties = await ref
          .read(profileSetupRepositoryProvider)
          .fetchFaculties();
      final user = ref.read(authControllerProvider).value?.user;
      if (user != null) _prefill(user, faculties);

      setState(() {
        _faculties = faculties;
        _loading = false;
      });
    } catch (_) {
      setState(() {
        _errorMessage = 'โหลดข้อมูลไม่สำเร็จ';
        _loading = false;
      });
    }
  }

  void _prefill(AppUser user, List<Faculty> faculties) {
    final nameThai = user.nameThai ?? '';
    var rest = nameThai;
    for (final prefix in _titlePrefixes) {
      if (rest.startsWith(prefix)) {
        _titlePrefix = prefix;
        rest = rest.substring(prefix.length);
        break;
      }
    }
    final spaceIndex = rest.indexOf(' ');
    if (spaceIndex == -1) {
      _firstNameController.text = rest;
    } else {
      _firstNameController.text = rest.substring(0, spaceIndex);
      _lastNameController.text = rest.substring(spaceIndex + 1).trim();
    }

    _studentIdController.text = user.studentId ?? '';
    _enrollmentYearController.text = user.enrollmentYear?.toString() ?? '';
    _yearLevel = user.yearLevel;
    _programType = user.programType ?? 'normal';

    if (user.facultyId != null) {
      for (final f in faculties) {
        if (f.id == user.facultyId) {
          _faculty = f;
          break;
        }
      }
    }
    _majorId = user.majorId;
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
      _successMessage = null;
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
      setState(() {
        _successMessage = 'บันทึกข้อมูลสำเร็จ';
        _editing = false;
      });
    } on ApiException catch (e) {
      setState(
        () => _errorMessage =
            e.errors?.values.firstOrNull?.first?.toString() ?? e.message,
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

  void _cancelEdit() {
    final user = ref.read(authControllerProvider).value?.user;
    setState(() {
      if (user != null) _prefill(user, _faculties);
      _editing = false;
      _errorMessage = null;
    });
  }

  InputDecoration _decoration(String label, {IconData? icon}) {
    return InputDecoration(
      labelText: label,
      prefixIcon: icon != null ? Icon(icon, size: 20) : null,
    );
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(authControllerProvider).value?.user;

    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: _loading
            ? const Center(child: CircularProgressIndicator())
            : SingleChildScrollView(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    BrandHeader(
                      title: 'โปรไฟล์ของฉัน',
                      subtitle: user?.email,
                      actions: [
                        if (!_editing)
                          IconButton(
                            icon: const Icon(
                              Icons.edit_outlined,
                              color: Colors.white,
                            ),
                            tooltip: 'แก้ไขข้อมูล',
                            onPressed: () => setState(() => _editing = true),
                          ),
                        IconButton(
                          icon: const Icon(Icons.logout, color: Colors.white),
                          tooltip: 'ออกจากระบบ',
                          onPressed: () => ref
                              .read(authControllerProvider.notifier)
                              .logout(),
                        ),
                      ],
                    ),
                    Padding(
                      padding: const EdgeInsets.all(20),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Center(
                            child: CircleAvatar(
                              radius: 40,
                              backgroundColor: isDark
                                  ? AppColors.purple900.withValues(alpha: 0.4)
                                  : AppColors.purple100,
                              backgroundImage: user?.avatarUrl != null
                                  ? NetworkImage(user!.avatarUrl!)
                                  : null,
                              child: user?.avatarUrl == null
                                  ? Icon(
                                      Icons.person,
                                      size: 40,
                                      color: isDark
                                          ? AppColors.purple400
                                          : AppColors.purple700,
                                    )
                                  : null,
                            ),
                          ),
                          const SizedBox(height: 20),
                          Form(
                            key: _formKey,
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.stretch,
                              spacing: 16,
                              children: [
                                if (_errorMessage != null)
                                  _MessageBanner(
                                    text: _errorMessage!,
                                    isError: true,
                                  ),
                                if (_successMessage != null)
                                  _MessageBanner(
                                    text: _successMessage!,
                                    isError: false,
                                  ),
                                SectionCard(
                                  icon: Icons.badge_outlined,
                                  title: 'ข้อมูลส่วนตัว',
                                  children: [
                                    DropdownButtonFormField<String>(
                                      initialValue: _titlePrefix,
                                      isExpanded: true,
                                      decoration: _decoration('คำนำหน้า'),
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
                                      onChanged: !_editing
                                          ? null
                                          : (v) => setState(
                                              () => _titlePrefix = v!,
                                            ),
                                    ),
                                    TextFormField(
                                      controller: _firstNameController,
                                      enabled: _editing,
                                      decoration: _decoration('ชื่อ'),
                                      validator: (v) =>
                                          (v == null || v.trim().isEmpty)
                                          ? 'กรุณากรอกชื่อ'
                                          : null,
                                    ),
                                    TextFormField(
                                      controller: _lastNameController,
                                      enabled: _editing,
                                      decoration: _decoration('นามสกุล'),
                                      validator: (v) =>
                                          (v == null || v.trim().isEmpty)
                                          ? 'กรุณากรอกนามสกุล'
                                          : null,
                                    ),
                                    TextFormField(
                                      controller: _studentIdController,
                                      enabled: _editing,
                                      decoration: _decoration(
                                        'รหัสนักศึกษา (11 หลัก)',
                                      ),
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
                                      enabled: _editing,
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
                                      onChanged: !_editing
                                          ? null
                                          : (v) =>
                                                setState(() => _yearLevel = v),
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
                                      onChanged: !_editing
                                          ? null
                                          : (v) => setState(
                                              () => _programType = v!,
                                            ),
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
                                      onChanged: !_editing
                                          ? null
                                          : (v) => setState(() {
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
                                      onChanged: (!_editing || _faculty == null)
                                          ? null
                                          : (v) => setState(() => _majorId = v),
                                    ),
                                  ],
                                ),
                                if (!_editing)
                                  SizedBox(
                                    height: 52,
                                    child: OutlinedButton.icon(
                                      onPressed: () =>
                                          setState(() => _editing = true),
                                      icon: const Icon(Icons.edit_outlined),
                                      label: const Text('แก้ไขข้อมูล'),
                                    ),
                                  )
                                else
                                  Row(
                                    spacing: 12,
                                    children: [
                                      Expanded(
                                        child: SizedBox(
                                          height: 52,
                                          child: OutlinedButton(
                                            onPressed: _submitting
                                                ? null
                                                : _cancelEdit,
                                            child: const Text('ยกเลิก'),
                                          ),
                                        ),
                                      ),
                                      Expanded(
                                        child: SizedBox(
                                          height: 52,
                                          child: _submitting
                                              ? FilledButton.icon(
                                                  onPressed: null,
                                                  icon: const SizedBox(
                                                    width: 18,
                                                    height: 18,
                                                    child:
                                                        CircularProgressIndicator(
                                                          strokeWidth: 2,
                                                          color: Colors.white,
                                                        ),
                                                  ),
                                                  label: const Text(
                                                    'กำลังบันทึก...',
                                                  ),
                                                )
                                              : FilledButton(
                                                  onPressed: _submit,
                                                  child: const Text('บันทึก'),
                                                ),
                                        ),
                                      ),
                                    ],
                                  ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
      ),
    );
  }
}

class _MessageBanner extends StatelessWidget {
  const _MessageBanner({required this.text, required this.isError});

  final String text;
  final bool isError;

  @override
  Widget build(BuildContext context) {
    final color = isError ? Colors.red : AppColors.green600;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: color.withValues(alpha: 0.2)),
      ),
      child: Text(
        text,
        style: TextStyle(
          color: color,
          fontSize: 13,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}
