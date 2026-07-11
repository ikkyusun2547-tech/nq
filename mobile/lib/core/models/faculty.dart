class Major {
  Major({required this.id, required this.nameTh, this.nameEn, this.degreeAbbr});

  final int id;
  final String nameTh;
  final String? nameEn;
  final String? degreeAbbr;

  factory Major.fromJson(Map<String, dynamic> json) {
    return Major(
      id: json['id'] as int,
      nameTh: json['name_th'] as String,
      nameEn: json['name_en'] as String?,
      degreeAbbr: json['degree_abbr'] as String?,
    );
  }
}

class Faculty {
  Faculty({required this.id, required this.nameTh, this.nameEn, this.majors = const []});

  final int id;
  final String nameTh;
  final String? nameEn;
  final List<Major> majors;

  factory Faculty.fromJson(Map<String, dynamic> json) {
    return Faculty(
      id: json['id'] as int,
      nameTh: json['name_th'] as String,
      nameEn: json['name_en'] as String?,
      majors: (json['majors'] as List<dynamic>? ?? [])
          .map((m) => Major.fromJson(m as Map<String, dynamic>))
          .toList()
        ..sort((a, b) => a.nameTh.compareTo(b.nameTh)),
    );
  }
}
