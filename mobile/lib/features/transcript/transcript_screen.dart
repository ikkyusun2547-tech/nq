import 'dart:io';
import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:path_provider/path_provider.dart';
import 'package:pdfx/pdfx.dart';
import 'package:share_plus/share_plus.dart';

import '../../core/providers.dart';
import '../../core/theme.dart';
import '../../core/widgets/section_card.dart';

class TranscriptScreen extends ConsumerStatefulWidget {
  const TranscriptScreen({super.key});

  @override
  ConsumerState<TranscriptScreen> createState() => _TranscriptScreenState();
}

class _TranscriptScreenState extends ConsumerState<TranscriptScreen> {
  PdfController? _controller;
  String? _savedPath;
  bool _loading = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final bytes = await ref
          .read(transcriptRepositoryProvider)
          .downloadBytes();
      final tempDir = await getTemporaryDirectory();
      final file = File('${tempDir.path}/activity-summary.pdf');
      await file.writeAsBytes(bytes);

      setState(() {
        _controller = PdfController(
          document: PdfDocument.openData(Uint8List.fromList(bytes)),
        );
        _savedPath = file.path;
        _loading = false;
      });
    } catch (_) {
      setState(() {
        _errorMessage = 'ดาวน์โหลดเอกสารไม่สำเร็จ';
        _loading = false;
      });
    }
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final tokens = context.surfaceColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            BrandHeader(
              title: 'ใบสรุปกิจกรรม',
              subtitle: 'เอกสารรับรองชั่วโมงกิจกรรมนักศึกษา',
              leading: IconButton(
                icon: const Icon(Icons.arrow_back, color: Colors.white),
                onPressed: () => Navigator.of(context).pop(),
              ),
              actions: [
                if (_savedPath != null)
                  IconButton(
                    icon: const Icon(Icons.share_outlined, color: Colors.white),
                    tooltip: 'แชร์',
                    onPressed: () => SharePlus.instance.share(
                      ShareParams(files: [XFile(_savedPath!)]),
                    ),
                  ),
              ],
            ),
            Expanded(
              child: _loading
                  ? const Center(
                      child: CircularProgressIndicator(
                        color: AppColors.purple600,
                      ),
                    )
                  : _errorMessage != null
                  ? Center(
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: SectionCard(
                          icon: Icons.error_outline,
                          title: 'เกิดข้อผิดพลาด',
                          children: [
                            Text(_errorMessage!, textAlign: TextAlign.center),
                            FilledButton.icon(
                              onPressed: () {
                                setState(() => _loading = true);
                                _load();
                              },
                              icon: const Icon(Icons.refresh),
                              label: const Text('ลองอีกครั้ง'),
                            ),
                          ],
                        ),
                      ),
                    )
                  : Stack(
                      alignment: Alignment.bottomCenter,
                      children: [
                        Padding(
                          padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
                          child: Container(
                            width: double.infinity,
                            decoration: BoxDecoration(
                              color: tokens.surface,
                              borderRadius: BorderRadius.circular(20),
                              border: Border.all(color: tokens.border),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withValues(
                                    alpha: isDark ? 0.25 : 0.06,
                                  ),
                                  blurRadius: 16,
                                  offset: const Offset(0, 4),
                                ),
                              ],
                            ),
                            clipBehavior: Clip.antiAlias,
                            child: PdfView(controller: _controller!),
                          ),
                        ),
                        Padding(
                          padding: const EdgeInsets.only(bottom: 20),
                          child: PdfPageNumber(
                            controller: _controller!,
                            builder:
                                (context, loadingState, page, pagesCount) =>
                                    Container(
                                      padding: const EdgeInsets.symmetric(
                                        horizontal: 14,
                                        vertical: 7,
                                      ),
                                      decoration: BoxDecoration(
                                        color: AppColors.purple950.withValues(
                                          alpha: 0.85,
                                        ),
                                        borderRadius: BorderRadius.circular(20),
                                      ),
                                      child: Text(
                                        'หน้า $page จาก ${pagesCount ?? '-'}',
                                        style: const TextStyle(
                                          fontSize: 12,
                                          fontWeight: FontWeight.w600,
                                          color: Colors.white,
                                        ),
                                      ),
                                    ),
                          ),
                        ),
                      ],
                    ),
            ),
          ],
        ),
      ),
    );
  }
}
