class CreditTransferPosition {
  CreditTransferPosition({required this.key, required this.label, required this.hours});

  final String key;
  final String label;
  final int hours;

  factory CreditTransferPosition.fromJson(Map<String, dynamic> json) {
    return CreditTransferPosition(
      key: json['key'] as String,
      label: json['label'] as String,
      hours: json['hours'] as int,
    );
  }
}

class CreditTransferRequest {
  CreditTransferRequest({
    required this.id,
    required this.position,
    required this.positionLabel,
    required this.academicYear,
    required this.hoursRequested,
    this.hoursApproved,
    required this.hoursCredited,
    required this.status,
    this.rejectReason,
  });

  final int id;
  final String position;
  final String positionLabel;
  final int academicYear;
  final int hoursRequested;
  final int? hoursApproved;
  final int hoursCredited;
  final String status;
  final String? rejectReason;

  factory CreditTransferRequest.fromJson(Map<String, dynamic> json) {
    return CreditTransferRequest(
      id: json['id'] as int,
      position: json['position'] as String,
      positionLabel: json['position_label'] as String,
      academicYear: json['academic_year'] as int,
      hoursRequested: json['hours_requested'] as int,
      hoursApproved: json['hours_approved'] as int?,
      hoursCredited: json['hours_credited'] as int,
      status: json['status'] as String,
      rejectReason: json['reject_reason'] as String?,
    );
  }
}
