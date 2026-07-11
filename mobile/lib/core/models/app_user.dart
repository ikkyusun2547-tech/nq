import 'faculty.dart';

/// Mirrors App\Http\Resources\UserResource's JSON shape.
class AppUser {
  AppUser({
    required this.id,
    required this.name,
    this.nameThai,
    required this.email,
    this.avatarUrl,
    this.studentId,
    required this.role,
    this.facultyId,
    this.majorId,
    this.faculty,
    this.major,
    this.enrollmentYear,
    this.yearLevel,
    this.programType,
    required this.accountStatus,
    required this.profileCompleted,
  });

  final int id;
  final String name;
  final String? nameThai;
  final String email;
  final String? avatarUrl;
  final String? studentId;
  final String role;
  final int? facultyId;
  final int? majorId;
  final Faculty? faculty;
  final Major? major;
  final int? enrollmentYear;
  final int? yearLevel;
  final String? programType;
  final String accountStatus;
  final bool profileCompleted;

  bool get isAdmin => role == 'admin' || role == 'super_admin';

  factory AppUser.fromJson(Map<String, dynamic> json) {
    // Faculty/major resources return `[]` (not `{}`) when the relation
    // wasn't loaded or is empty — same PHP-empty-array-to-JSON-list quirk
    // seen elsewhere in this API, so guard with an `is Map` check.
    final facultyJson = json['faculty'];
    final majorJson = json['major'];

    return AppUser(
      id: json['id'] as int,
      name: json['name'] as String,
      nameThai: json['name_thai'] as String?,
      email: json['email'] as String,
      avatarUrl: json['avatar_url'] as String?,
      studentId: json['student_id'] as String?,
      role: json['role'] as String,
      facultyId: json['faculty_id'] as int?,
      majorId: json['major_id'] as int?,
      faculty: facultyJson is Map && facultyJson.isNotEmpty
          ? Faculty.fromJson(facultyJson.cast<String, dynamic>())
          : null,
      major: majorJson is Map && majorJson.isNotEmpty ? Major.fromJson(majorJson.cast<String, dynamic>()) : null,
      enrollmentYear: json['enrollment_year'] as int?,
      yearLevel: json['year_level'] as int?,
      programType: json['program_type'] as String?,
      accountStatus: json['account_status'] as String,
      profileCompleted: json['profile_completed'] as bool? ?? false,
    );
  }
}
