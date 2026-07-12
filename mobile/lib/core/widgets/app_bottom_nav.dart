import 'package:flutter/material.dart';

import '../theme.dart';

/// Same bottom tab bar HomeShell shows for its 4 tabs — reused by screens
/// pushed on top of it (the dashboard's QR-scan / external-activity /
/// credit-transfer quick actions) so a student can jump straight to another
/// tab instead of dead-ending with only a back button. [currentIndex] is
/// the tab to highlight; pass an out-of-range value (e.g. -1) to highlight
/// none, for a screen that isn't itself one of the 4 tabs.
class AppBottomNav extends StatelessWidget {
  const AppBottomNav({
    super.key,
    required this.currentIndex,
    required this.onTap,
  });

  final int currentIndex;
  final ValueChanged<int> onTap;

  static const items = [
    _NavItem(
      icon: Icons.dashboard_outlined,
      activeIcon: Icons.dashboard_rounded,
      label: 'ภาพรวม',
    ),
    _NavItem(
      icon: Icons.calendar_month_outlined,
      activeIcon: Icons.calendar_month_rounded,
      label: 'กิจกรรม',
    ),
    _NavItem(
      icon: Icons.fact_check_outlined,
      activeIcon: Icons.fact_check_rounded,
      label: 'ขอชั่วโมง',
    ),
    _NavItem(
      icon: Icons.account_circle_outlined,
      activeIcon: Icons.account_circle_rounded,
      label: 'โปรไฟล์',
    ),
  ];

  @override
  Widget build(BuildContext context) {
    final tokens = context.surfaceColors;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: tokens.surface,
        borderRadius: const BorderRadius.only(
          topLeft: Radius.circular(24),
          topRight: Radius.circular(24),
        ),
        boxShadow: [
          BoxShadow(
            color: AppColors.purple900.withValues(alpha: 0.12),
            blurRadius: 20,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: SafeArea(
        top: false,
        child: Row(
          children: List.generate(items.length, (i) {
            final selected = i == currentIndex;
            final item = items[i];

            return Expanded(
              child: GestureDetector(
                behavior: HitTestBehavior.opaque,
                onTap: () => onTap(i),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 220),
                  curve: Curves.easeOut,
                  padding: const EdgeInsets.symmetric(vertical: 6),
                  decoration: BoxDecoration(
                    color: selected ? AppColors.purple700 : Colors.transparent,
                    borderRadius: BorderRadius.circular(18),
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        selected ? item.activeIcon : item.icon,
                        size: 20,
                        color: selected ? Colors.white : tokens.textSecondary,
                      ),
                      const SizedBox(height: 2),
                      Text(
                        item.label,
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: selected
                              ? FontWeight.w700
                              : FontWeight.w500,
                          color: selected ? Colors.white : tokens.textSecondary,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            );
          }),
        ),
      ),
    );
  }
}

class _NavItem {
  const _NavItem({
    required this.icon,
    required this.activeIcon,
    required this.label,
  });

  final IconData icon;
  final IconData activeIcon;
  final String label;
}
