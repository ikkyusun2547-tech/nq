import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/models/dashboard.dart';
import '../../core/providers.dart';
import '../../core/theme.dart';
import '../../core/widgets/feed_item.dart';
import '../../core/widgets/section_card.dart';

/// Mobile counterpart of resources/views/student/activity-history/index.blade.php
/// — the "ดูทั้งหมด" destination behind the dashboard's three preview lists,
/// each capped at 5 rows there.
class ActivityHistoryScreen extends ConsumerStatefulWidget {
  const ActivityHistoryScreen({super.key, required this.initialStatus});

  /// 'approved' | 'pending' | 'rejected' — which tab opens first, matching
  /// whichever section's "ดูทั้งหมด" the student tapped.
  final String initialStatus;

  @override
  ConsumerState<ActivityHistoryScreen> createState() =>
      _ActivityHistoryScreenState();
}

class _ActivityHistoryScreenState extends ConsumerState<ActivityHistoryScreen> {
  static const _tabs = {
    'approved': 'อนุมัติแล้ว',
    'pending': 'รออนุมัติ',
    'rejected': 'ถูกปฏิเสธ',
  };

  late String _status = widget.initialStatus;
  final List<DashboardFeedItem> _items = [];
  int _currentPage = 1;
  int _lastPage = 1;
  bool _loading = true;
  bool _loadingMore = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final page = await ref
          .read(activityHistoryRepositoryProvider)
          .fetch(status: _status);
      if (!mounted) return;
      setState(() {
        _items
          ..clear()
          ..addAll(page.items);
        _currentPage = page.currentPage;
        _lastPage = page.lastPage;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = 'โหลดข้อมูลไม่สำเร็จ: $e';
        _loading = false;
      });
    }
  }

  Future<void> _loadMore() async {
    if (_loadingMore || _currentPage >= _lastPage) return;

    setState(() => _loadingMore = true);
    try {
      final page = await ref
          .read(activityHistoryRepositoryProvider)
          .fetch(status: _status, page: _currentPage + 1);
      if (!mounted) return;
      setState(() {
        _items.addAll(page.items);
        _currentPage = page.currentPage;
        _lastPage = page.lastPage;
        _loadingMore = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _loadingMore = false);
    }
  }

  void _selectStatus(String status) {
    if (status == _status) return;
    setState(() => _status = status);
    _load();
  }

  @override
  Widget build(BuildContext context) {
    final statusColor = switch (_status) {
      'pending' => AppColors.statusPending,
      'approved' => AppColors.statusApproved,
      _ => AppColors.statusRejected,
    };

    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: RefreshIndicator(
          onRefresh: _load,
          child: ListView(
            padding: EdgeInsets.zero,
            children: [
              BrandHeader(
                title: 'ประวัติกิจกรรมของฉัน',
                leading: IconButton(
                  icon: const Icon(Icons.arrow_back, color: Colors.white),
                  onPressed: () => Navigator.of(context).pop(),
                ),
              ),
              Padding(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  spacing: 16,
                  children: [
                    Row(
                      spacing: 8,
                      children: _tabs.entries.map((entry) {
                        final active = entry.key == _status;
                        return Expanded(
                          child: OutlinedButton(
                            onPressed: () => _selectStatus(entry.key),
                            style: OutlinedButton.styleFrom(
                              backgroundColor: active
                                  ? AppColors.purple700
                                  : null,
                              foregroundColor: active
                                  ? Colors.white
                                  : AppColors.purple700,
                              side: BorderSide(
                                color: active
                                    ? AppColors.purple700
                                    : AppColors.purple100,
                              ),
                            ),
                            child: Text(
                              entry.value,
                              style: const TextStyle(fontSize: 12.5),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        );
                      }).toList(),
                    ),
                    if (_loading)
                      const Padding(
                        padding: EdgeInsets.only(top: 40),
                        child: Center(child: CircularProgressIndicator()),
                      )
                    else if (_error != null)
                      Center(child: Text(_error!))
                    else if (_items.isEmpty)
                      Padding(
                        padding: const EdgeInsets.only(top: 40),
                        child: Center(
                          child: Text(
                            'ไม่มีรายการในหมวดนี้',
                            style: TextStyle(
                              color: context.surfaceColors.textSecondary,
                            ),
                          ),
                        ),
                      )
                    else
                      SectionCard(
                        icon: Icons.checklist_outlined,
                        title: _tabs[_status]!,
                        children: _items
                            .map(
                              (item) => FeedRow(
                                item: item,
                                statusColor: statusColor,
                                icon: feedTypeIcon(item.type),
                                typeLabel: feedTypeLabel(item),
                                onTap: _status == 'pending'
                                    ? null
                                    : () => openFeedItemDetail(context, item),
                              ),
                            )
                            .toList(),
                      ),
                    if (!_loading && _currentPage < _lastPage)
                      Center(
                        child: TextButton(
                          onPressed: _loadingMore ? null : _loadMore,
                          child: _loadingMore
                              ? const SizedBox(
                                  width: 18,
                                  height: 18,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                  ),
                                )
                              : const Text('โหลดเพิ่มเติม'),
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
