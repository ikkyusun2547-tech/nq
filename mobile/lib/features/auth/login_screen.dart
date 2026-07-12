import 'dart:ui';

import 'package:flutter/foundation.dart' show kDebugMode;
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_sign_in/google_sign_in.dart';

import '../../core/api_exception.dart';
import '../../core/theme.dart';
import '../../core/widgets/google_logo.dart';
import 'auth_controller.dart';

/// Same OAuth Client ID as the backend's .env GOOGLE_CLIENT_ID — required so
/// the id_token's `aud` claim matches what
/// App\Services\Auth\GoogleIdTokenVerifier checks server-side. Client IDs
/// aren't secret (unlike the client_secret, which mobile never uses).
const _googleServerClientId = String.fromEnvironment(
  'GOOGLE_SERVER_CLIENT_ID',
  defaultValue:
      '138426260507-d91v1ijfcfn5np8sgjld0r1f35ndpts8.apps.googleusercontent.com',
);

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  bool _initialized = false;
  bool _signingIn = false;
  bool _testSigningIn = false;
  String? _errorMessage;

  Future<GoogleSignIn> _signIn() async {
    final signIn = GoogleSignIn.instance;
    if (!_initialized) {
      await signIn.initialize(serverClientId: _googleServerClientId);
      _initialized = true;
    }

    return signIn;
  }

  Future<void> _handleSignIn() async {
    setState(() {
      _signingIn = true;
      _errorMessage = null;
    });

    try {
      final signIn = await _signIn();
      final account = await signIn.authenticate();
      final idToken = account.authentication.idToken;

      if (idToken == null) {
        throw Exception('Google did not return an id_token');
      }

      await ref
          .read(authControllerProvider.notifier)
          .loginWithGoogleIdToken(idToken);
    } on GoogleSignInException catch (e) {
      if (e.code != GoogleSignInExceptionCode.canceled) {
        setState(
          () => _errorMessage = 'เข้าสู่ระบบไม่สำเร็จ กรุณาลองใหม่อีกครั้ง',
        );
      }
    } catch (_) {
      // loginWithGoogleIdToken never rethrows (it runs inside
      // AsyncValue.guard) — a backend failure surfaces instead through the
      // ref.listen below, which sets _errorMessage from the resulting
      // AsyncError. This branch only ever catches something going wrong
      // before that call (e.g. the id_token extraction above).
      setState(
        () => _errorMessage = 'เข้าสู่ระบบไม่สำเร็จ กรุณาลองใหม่อีกครั้ง',
      );
    } finally {
      if (mounted) {
        setState(() => _signingIn = false);
      }
    }
  }

  /// Debug-only shortcut for previewing the app as a seeded account without
  /// a real @srru.ac.th Google login — see AuthRepository.loginAsTestUser.
  /// User id 2 is the first seeded student in the demo dataset.
  Future<void> _handleTestSignIn() async {
    setState(() {
      _testSigningIn = true;
      _errorMessage = null;
    });

    try {
      await ref.read(authControllerProvider.notifier).loginAsTestUser(2);
    } catch (_) {
      // See the matching comment in _handleSignIn — loginAsTestUser also
      // runs inside AsyncValue.guard, so a backend error surfaces through
      // ref.listen below, not here.
      setState(
        () => _errorMessage = 'เข้าสู่ระบบทดสอบไม่สำเร็จ กรุณาลองใหม่อีกครั้ง',
      );
    } finally {
      if (mounted) {
        setState(() => _testSigningIn = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    ref.listen(authControllerProvider, (previous, next) {
      next.whenOrNull(
        error: (error, _) {
          if (error is ApiException) {
            setState(() => _errorMessage = error.message);
          }
        },
      );
    });

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light,
      child: Scaffold(
        body: Stack(
          children: [
            // Full-bleed brand gradient, same as the web login page.
            Container(
              decoration: const BoxDecoration(
                gradient: AppColors.brandGradient,
              ),
            ),

            // Soft blurred glow blobs, mirroring the web version's decorative circles.
            Positioned(
              top: -100,
              left: -100,
              child: _GlowBlob(
                color: Colors.white.withValues(alpha: 0.10),
                size: 280,
              ),
            ),
            Positioned(
              bottom: -100,
              right: -100,
              child: _GlowBlob(
                color: AppColors.green500.withValues(alpha: 0.15),
                size: 280,
              ),
            ),

            SafeArea(
              child: Center(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 24,
                    vertical: 32,
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      _GlassCard(
                        child: Padding(
                          padding: const EdgeInsets.all(28),
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Container(
                                width: 84,
                                height: 84,
                                padding: const EdgeInsets.all(4),
                                decoration: BoxDecoration(
                                  color: Colors.white,
                                  shape: BoxShape.circle,
                                  boxShadow: [
                                    BoxShadow(
                                      color: Colors.black.withValues(
                                        alpha: 0.08,
                                      ),
                                      blurRadius: 16,
                                      offset: const Offset(0, 6),
                                    ),
                                  ],
                                ),
                                child: ClipOval(
                                  child: Image.asset(
                                    'assets/images/logo.png',
                                    fit: BoxFit.contain,
                                    errorBuilder: (_, _, _) => Icon(
                                      Icons.school,
                                      color: AppColors.purple700,
                                      size: 40,
                                    ),
                                  ),
                                ),
                              ),
                              const SizedBox(height: 20),
                              const Text(
                                'SRRU Check',
                                style: TextStyle(
                                  fontSize: 20,
                                  fontWeight: FontWeight.w700,
                                  color: Color(0xFF1E1B2E),
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                'มหาวิทยาลัยราชภัฏสุรินทร์',
                                style: TextStyle(
                                  fontSize: 13,
                                  color: Colors.grey.shade600,
                                ),
                              ),
                              const SizedBox(height: 28),
                              if (_errorMessage != null) ...[
                                Container(
                                  width: double.infinity,
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 16,
                                    vertical: 12,
                                  ),
                                  decoration: BoxDecoration(
                                    color: AppColors.statusRejected.withValues(
                                      alpha: 0.08,
                                    ),
                                    borderRadius: BorderRadius.circular(14),
                                    border: Border.all(
                                      color: AppColors.statusRejected
                                          .withValues(alpha: 0.2),
                                    ),
                                  ),
                                  child: Text(
                                    _errorMessage!,
                                    style: const TextStyle(
                                      color: AppColors.statusRejected,
                                      fontSize: 13,
                                    ),
                                    textAlign: TextAlign.center,
                                  ),
                                ),
                                const SizedBox(height: 16),
                              ],
                              SizedBox(
                                width: double.infinity,
                                child: OutlinedButton(
                                  onPressed: _signingIn ? null : _handleSignIn,
                                  style: OutlinedButton.styleFrom(
                                    backgroundColor: Colors.white,
                                    side: BorderSide(
                                      color: Colors.grey.shade300,
                                    ),
                                  ),
                                  child: _signingIn
                                      ? const SizedBox(
                                          width: 18,
                                          height: 18,
                                          child: CircularProgressIndicator(
                                            strokeWidth: 2,
                                          ),
                                        )
                                      : Row(
                                          mainAxisSize: MainAxisSize.min,
                                          children: [
                                            const GoogleLogo(size: 20),
                                            const SizedBox(width: 12),
                                            const Text(
                                              'เข้าสู่ระบบด้วย Google',
                                              style: TextStyle(
                                                color: Color(0xFF1E1B2E),
                                                fontWeight: FontWeight.w600,
                                              ),
                                            ),
                                          ],
                                        ),
                                ),
                              ),
                              const SizedBox(height: 16),
                              Text(
                                'ใช้ได้เฉพาะอีเมลมหาวิทยาลัย (@srru.ac.th) เท่านั้น',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.grey.shade500,
                                ),
                                textAlign: TextAlign.center,
                              ),
                              if (kDebugMode) ...[
                                const SizedBox(height: 20),
                                Row(
                                  children: [
                                    Expanded(
                                      child: Divider(
                                        color: Colors.grey.shade300,
                                      ),
                                    ),
                                    Padding(
                                      padding: const EdgeInsets.symmetric(
                                        horizontal: 10,
                                      ),
                                      child: Text(
                                        'DEBUG ONLY',
                                        style: TextStyle(
                                          fontSize: 10,
                                          letterSpacing: 0.5,
                                          color: Colors.grey.shade400,
                                        ),
                                      ),
                                    ),
                                    Expanded(
                                      child: Divider(
                                        color: Colors.grey.shade300,
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 12),
                                SizedBox(
                                  width: double.infinity,
                                  child: TextButton(
                                    onPressed: _testSigningIn
                                        ? null
                                        : _handleTestSignIn,
                                    child: _testSigningIn
                                        ? const SizedBox(
                                            width: 16,
                                            height: 16,
                                            child: CircularProgressIndicator(
                                              strokeWidth: 2,
                                            ),
                                          )
                                        : const Text(
                                            'พรีวิวมุมมองนักศึกษา (ไม่ผ่าน Google)',
                                          ),
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 20),
                      Text(
                        'กองพัฒนานักศึกษา · มหาวิทยาลัยราชภัฏสุรินทร์',
                        style: TextStyle(
                          fontSize: 12,
                          color: AppColors.purple100,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _GlowBlob extends StatelessWidget {
  const _GlowBlob({required this.color, required this.size});

  final Color color;
  final double size;

  @override
  Widget build(BuildContext context) {
    return IgnorePointer(
      child: Container(
        width: size,
        height: size,
        decoration: BoxDecoration(shape: BoxShape.circle, color: color),
      ),
    );
  }
}

/// Frosted-glass card matching the web app's `.glass-card` style.
class _GlassCard extends StatelessWidget {
  const _GlassCard({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(28),
      child: BackdropFilter(
        filter: ImageFilter.blur(sigmaX: 20, sigmaY: 20),
        child: Container(
          constraints: const BoxConstraints(maxWidth: 380),
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.92),
            borderRadius: BorderRadius.circular(28),
            border: Border.all(
              color: Colors.white.withValues(alpha: 0.4),
              width: 1.5,
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.25),
                blurRadius: 40,
                offset: const Offset(0, 20),
              ),
            ],
          ),
          child: child,
        ),
      ),
    );
  }
}
