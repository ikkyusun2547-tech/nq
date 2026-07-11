class ExternalActivityRequest {
  ExternalActivityRequest({
    required this.id,
    required this.title,
    required this.organization,
    required this.activityDate,
    required this.activityCategory,
    required this.hoursRequested,
    this.hoursApproved,
    required this.hoursCredited,
    required this.status,
    this.rejectReason,
  });

  final int id;
  final String title;
  final String organization;
  final String activityDate;
  final String activityCategory;
  final int hoursRequested;
  final int? hoursApproved;
  final int hoursCredited;
  final String status;
  final String? rejectReason;

  factory ExternalActivityRequest.fromJson(Map<String, dynamic> json) {
    return ExternalActivityRequest(
      id: json['id'] as int,
      title: json['title'] as String,
      organization: json['organization'] as String,
      activityDate: json['activity_date'] as String,
      activityCategory: json['activity_category'] as String,
      hoursRequested: json['hours_requested'] as int,
      hoursApproved: json['hours_approved'] as int?,
      hoursCredited: json['hours_credited'] as int,
      status: json['status'] as String,
      rejectReason: json['reject_reason'] as String?,
    );
  }
}
