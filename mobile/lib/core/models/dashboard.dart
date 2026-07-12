class DashboardSummary {
  DashboardSummary({
    required this.totalActivities,
    required this.requiredActivities,
    required this.totalHours,
    required this.requiredHours,
    this.currentYear,
    this.yearlyTargetHours,
    required this.categoryHours,
    required this.isCleared,
  });

  final int totalActivities;
  final int requiredActivities;
  final int totalHours;
  final int requiredHours;
  final int? currentYear;
  final int? yearlyTargetHours;
  final Map<String, int> categoryHours;
  final bool isCleared;

  factory DashboardSummary.fromJson(Map<String, dynamic> json) {
    return DashboardSummary(
      totalActivities: json['total_activities'] as int,
      requiredActivities: json['required_activities'] as int,
      totalHours: json['total_hours'] as int,
      requiredHours: json['required_hours'] as int,
      currentYear: json['current_year'] as int?,
      yearlyTargetHours: json['yearly_target_hours'] as int?,
      categoryHours: (json['category_hours'] as Map<String, dynamic>).map(
        (k, v) => MapEntry(k, v as int),
      ),
      isCleared: json['is_cleared'] as bool,
    );
  }
}

class DashboardFeedItem {
  DashboardFeedItem({
    this.title,
    this.date,
    this.hours,
    this.type,
    this.isApproved,
    this.activityId,
    this.checkinMethod,
    this.locationName,
    this.photoUrl,
    this.rejectReason,
    this.flagReason,
  });

  final String? title;
  final String? date;
  final int? hours;
  final String? type;
  final bool? isApproved;
  final int? activityId;
  final String? checkinMethod;
  final String? locationName;
  final String? photoUrl;
  final String? rejectReason;

  /// Why an auto-flagged check-in (e.g. GPS out of bounds, device sharing
  /// suspected) is sitting in "รอตรวจสอบ" instead of auto-approved.
  final String? flagReason;

  factory DashboardFeedItem.fromJson(Map<String, dynamic> json) {
    return DashboardFeedItem(
      title: json['title'] as String?,
      date: json['date'] as String?,
      hours: json['hours'] as int?,
      type: json['type'] as String?,
      isApproved: json['is_approved'] as bool?,
      activityId: json['activity_id'] as int?,
      checkinMethod: json['checkin_method'] as String?,
      locationName: json['location_name'] as String?,
      photoUrl: json['photo_url'] as String?,
      rejectReason: json['reject_reason'] as String?,
      flagReason: json['flag_reason'] as String?,
    );
  }
}
