class Activity {
  Activity({
    required this.id,
    required this.title,
    this.description,
    this.bannerUrl,
    this.organizerName,
    this.activityLevel,
    required this.activityCategory,
    this.activityType,
    this.creditHours,
    this.startAt,
    this.endAt,
    this.locationName,
    this.checkinMethod,
    this.checkinOpensAt,
    this.checkinClosesAt,
    required this.status,
    this.wasRecentlyUpdatedSignificantly = false,
  });

  final int id;
  final String title;
  final String? description;
  final String? bannerUrl;
  final String? organizerName;
  final String? activityLevel;
  final String activityCategory;
  final String? activityType;
  final int? creditHours;
  final String? startAt;
  final String? endAt;
  final String? locationName;
  final String? checkinMethod;
  final String? checkinOpensAt;
  final String? checkinClosesAt;
  final String status;
  final bool wasRecentlyUpdatedSignificantly;

  bool get usesSelfReportCheckIn => checkinMethod == 'self_report';

  factory Activity.fromJson(Map<String, dynamic> json) {
    return Activity(
      id: json['id'] as int,
      title: json['title'] as String,
      description: json['description'] as String?,
      bannerUrl: json['banner_url'] as String?,
      organizerName: json['organizer_name'] as String?,
      activityLevel: json['activity_level'] as String?,
      activityCategory: json['activity_category'] as String,
      activityType: json['activity_type'] as String?,
      creditHours: json['credit_hours'] as int?,
      startAt: json['start_at'] as String?,
      endAt: json['end_at'] as String?,
      locationName: json['location_name'] as String?,
      checkinMethod: json['checkin_method'] as String?,
      checkinOpensAt: json['checkin_opens_at'] as String?,
      checkinClosesAt: json['checkin_closes_at'] as String?,
      status: json['status'] as String,
      wasRecentlyUpdatedSignificantly: json['was_recently_updated_significantly'] as bool? ?? false,
    );
  }
}
