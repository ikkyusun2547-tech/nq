import 'package:flutter/material.dart';

/// Mirrors the web app's brand palette (resources/css/app.css) so the
/// mobile app reads as the same product, not a separate one. These accent
/// colors (gradient, category dots, status colors, purple buttons) stay the
/// same across light/dark — only neutral surfaces/text flip, via
/// [AppSurfaceColors] below.
class AppColors {
  static const purple50 = Color(0xFFF5F3FF);
  static const purple100 = Color(0xFFEDE9FE);
  static const purple200 = Color(0xFFDDD6FE);
  static const purple400 = Color(0xFFA78BFA);
  static const purple500 = Color(0xFF8B5CF6);
  static const purple600 = Color(0xFF7C3AED);
  static const purple700 = Color(0xFF6D28D9);
  static const purple800 = Color(0xFF5B21B6);
  static const purple900 = Color(0xFF4C1D95);
  static const purple950 = Color(0xFF2E1065);

  static const green50 = Color(0xFFECFDF5);
  static const green400 = Color(0xFF34D399);
  static const green500 = Color(0xFF10B981);
  static const green600 = Color(0xFF059669);
  static const green700 = Color(0xFF047857);

  /// Same 135deg purple950 -> purple800 (55%) -> green600 gradient as the
  /// web login page's `.brand-gradient` class and the transcript PDF header.
  static const brandGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [purple950, purple800, green600],
    stops: [0.0, 0.55, 1.0],
  );

  /// Same per-category dot colors as the web dashboard / transcript PDF
  /// (Student\TranscriptController::CATEGORY_COLORS).
  static const categoryColors = {
    'culture': Color(0xFF38BDF8),
    'academic': Color(0xFF10B981),
    'sports': Color(0xFFFBBF24),
    'volunteer': Color(0xFF8B5CF6),
    'ethics': Color(0xFFE879F9),
  };

  static const statusPending = Color(0xFFF59E0B);
  static const statusApproved = Color(0xFF10B981);
  static const statusRejected = Color(0xFFEF4444);

  static const darkScaffoldBg = Color(0xFF130F1F);
  static const darkSurface = Color(0xFF1E1830);
  static const darkBorder = Color(0xFF362C50);
  static const darkTextPrimary = Color(0xFFF3F1F8);
  static const darkTextSecondary = Color(0xFFA79FC0);
}

/// Neutral surface/border/text tokens that flip between light and dark, so
/// screens read `context.surfaceColors.surface` instead of hardcoding
/// `Colors.white` / `AppColors.purple50` / `Colors.grey.shade600` — the
/// pattern that was littered across every screen before dark mode existed.
class AppSurfaceColors extends ThemeExtension<AppSurfaceColors> {
  const AppSurfaceColors({
    required this.scaffoldBg,
    required this.surface,
    required this.border,
    required this.textPrimary,
    required this.textSecondary,
  });

  final Color scaffoldBg;
  final Color surface;
  final Color border;
  final Color textPrimary;
  final Color textSecondary;

  static const light = AppSurfaceColors(
    scaffoldBg: AppColors.purple50,
    surface: Colors.white,
    border: AppColors.purple100,
    textPrimary: Color(0xFF1E1B2E),
    textSecondary: Color(0xFF6B7280),
  );

  static const dark = AppSurfaceColors(
    scaffoldBg: AppColors.darkScaffoldBg,
    surface: AppColors.darkSurface,
    border: AppColors.darkBorder,
    textPrimary: AppColors.darkTextPrimary,
    textSecondary: AppColors.darkTextSecondary,
  );

  @override
  AppSurfaceColors copyWith({
    Color? scaffoldBg,
    Color? surface,
    Color? border,
    Color? textPrimary,
    Color? textSecondary,
  }) {
    return AppSurfaceColors(
      scaffoldBg: scaffoldBg ?? this.scaffoldBg,
      surface: surface ?? this.surface,
      border: border ?? this.border,
      textPrimary: textPrimary ?? this.textPrimary,
      textSecondary: textSecondary ?? this.textSecondary,
    );
  }

  @override
  AppSurfaceColors lerp(ThemeExtension<AppSurfaceColors>? other, double t) {
    if (other is! AppSurfaceColors) return this;
    return AppSurfaceColors(
      scaffoldBg: Color.lerp(scaffoldBg, other.scaffoldBg, t)!,
      surface: Color.lerp(surface, other.surface, t)!,
      border: Color.lerp(border, other.border, t)!,
      textPrimary: Color.lerp(textPrimary, other.textPrimary, t)!,
      textSecondary: Color.lerp(textSecondary, other.textSecondary, t)!,
    );
  }
}

extension AppSurfaceColorsX on BuildContext {
  AppSurfaceColors get surfaceColors => Theme.of(this).extension<AppSurfaceColors>()!;
}

ThemeData buildAppTheme({Brightness brightness = Brightness.light}) {
  final isDark = brightness == Brightness.dark;
  final tokens = isDark ? AppSurfaceColors.dark : AppSurfaceColors.light;
  final colorScheme = ColorScheme.fromSeed(seedColor: AppColors.purple600, brightness: brightness);

  return ThemeData(
    useMaterial3: true,
    brightness: brightness,
    colorScheme: colorScheme,
    scaffoldBackgroundColor: tokens.scaffoldBg,
    extensions: [tokens],
    appBarTheme: const AppBarTheme(
      backgroundColor: AppColors.purple950,
      foregroundColor: Colors.white,
      elevation: 0,
      centerTitle: false,
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        backgroundColor: AppColors.purple700,
        foregroundColor: Colors.white,
        padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 20),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        textStyle: const TextStyle(fontWeight: FontWeight.w600),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        foregroundColor: AppColors.purple400,
        side: BorderSide(color: isDark ? AppColors.darkBorder : AppColors.purple200, width: 1.5),
        padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 20),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      ).copyWith(
        foregroundColor: WidgetStateProperty.all(isDark ? AppColors.purple400 : AppColors.purple700),
      ),
    ),
    cardTheme: CardThemeData(
      elevation: 0,
      color: tokens.surface,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(20),
        side: BorderSide(color: tokens.border, width: 1),
      ),
      margin: EdgeInsets.zero,
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: tokens.surface,
      labelStyle: TextStyle(color: tokens.textSecondary),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide(color: tokens.border),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide(color: tokens.border),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide(color: AppColors.purple600, width: 2),
      ),
      disabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide(color: tokens.border.withValues(alpha: 0.5)),
      ),
    ),
  );
}
