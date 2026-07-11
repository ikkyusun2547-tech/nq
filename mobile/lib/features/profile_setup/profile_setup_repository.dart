import '../../core/api_client.dart';
import '../../core/models/app_user.dart';
import '../../core/models/faculty.dart';

class ProfileSetupRepository {
  ProfileSetupRepository({required this.apiClient});

  final ApiClient apiClient;

  Future<List<Faculty>> fetchFaculties() async {
    final response = await apiClient.dio.get('/setup-profile');
    final data = response.data['faculties'] as List<dynamic>;

    return data.map((f) => Faculty.fromJson(f as Map<String, dynamic>)).toList();
  }

  Future<AppUser> submit({
    required String titlePrefix,
    required String firstName,
    required String lastName,
    required String studentId,
    required int enrollmentYear,
    required int yearLevel,
    required String programType,
    required int facultyId,
    required int majorId,
  }) async {
    final response = await apiClient.dio.post(
      '/setup-profile',
      data: {
        'title_prefix': titlePrefix,
        'first_name': firstName,
        'last_name': lastName,
        'student_id': studentId,
        'enrollment_year': enrollmentYear,
        'year_level': yearLevel,
        'program_type': programType,
        'faculty_id': facultyId,
        'major_id': majorId,
      },
    );

    return AppUser.fromJson(response.data['user'] as Map<String, dynamic>);
  }
}
