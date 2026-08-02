import 'dart:async';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:file_saver/file_saver.dart';
import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/models/contact.dart';
import '../../core/providers.dart';
import '../../core/theme.dart';
import 'attachment_picker.dart';

class ContactThreadScreen extends ConsumerStatefulWidget {
  const ContactThreadScreen({super.key, required this.threadId});

  final int threadId;

  @override
  ConsumerState<ContactThreadScreen> createState() => _ContactThreadScreenState();
}

class _ContactThreadScreenState extends ConsumerState<ContactThreadScreen> {
  final _bodyController = TextEditingController();
  final _scrollController = ScrollController();
  ContactThreadDetail? _detail;
  bool _loading = true;
  bool _sending = false;
  String? _errorMessage;
  String? _attachmentPath;
  Timer? _pollTimer;

  @override
  void initState() {
    super.initState();
    _load();
    // Picks up the other side's replies without the student needing to
    // back out and re-enter the thread — mirrors the web chat's poll().
    _pollTimer = Timer.periodic(const Duration(seconds: 5), (_) => _poll());
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    _bodyController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final detail = await ref.read(contactRepositoryProvider).fetchThread(widget.threadId);
      setState(() {
        _detail = detail;
        _loading = false;
      });
      WidgetsBinding.instance.addPostFrameCallback((_) => _scrollToBottom());
    } catch (_) {
      setState(() => _loading = false);
    }
  }

  /// Same fetch as _load(), but silent — no loading spinner, so a poll
  /// tick doesn't flash the whole screen every 5 seconds.
  Future<void> _poll() async {
    try {
      final detail = await ref.read(contactRepositoryProvider).fetchThread(widget.threadId);
      final grew = _detail == null || detail.messages.length > _detail!.messages.length;
      if (!mounted) return;
      setState(() => _detail = detail);
      if (grew) {
        WidgetsBinding.instance.addPostFrameCallback((_) => _scrollToBottom());
      }
    } catch (_) {
      // Silent — the next tick tries again.
    }
  }

  void _scrollToBottom() {
    if (!_scrollController.hasClients) return;
    _scrollController.jumpTo(_scrollController.position.maxScrollExtent);
  }

  Future<void> _pickAttachment() async {
    final path = await pickAttachment(context);
    if (path != null) setState(() => _attachmentPath = path);
  }

  Future<void> _send() async {
    final body = _bodyController.text.trim();
    if (body.isEmpty && _attachmentPath == null) return;

    setState(() {
      _sending = true;
      _errorMessage = null;
    });

    try {
      await ref.read(contactRepositoryProvider).reply(
            widget.threadId,
            body: body.isEmpty ? null : body,
            attachmentPath: _attachmentPath,
          );
      _bodyController.clear();
      setState(() => _attachmentPath = null);
      await _load();
    } catch (_) {
      setState(() => _errorMessage = 'ส่งข้อความไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final tokens = context.surfaceColors;
    final detail = _detail;

    return Scaffold(
      appBar: AppBar(title: Text(detail?.thread.subject ?? 'ข้อความ')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : detail == null
          ? Center(child: Text('โหลดข้อมูลไม่สำเร็จ', style: TextStyle(color: tokens.textSecondary)))
          : Column(
              children: [
                // Same "who's handling this" indicator as the web contact
                // chat — see Student\ContactController's contact/show.blade.php.
                if (detail.thread.assignedAdminName != null)
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    color: AppColors.purple50,
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.support_agent, size: 14, color: AppColors.purple600),
                        const SizedBox(width: 6),
                        Flexible(
                          child: Text(
                            'เจ้าหน้าที่ผู้ดูแลเรื่องนี้: ${detail.thread.assignedAdminName}',
                            style: const TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w600,
                              color: AppColors.purple700,
                            ),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  ),
                Expanded(
                  child: ListView.builder(
                    controller: _scrollController,
                    padding: const EdgeInsets.all(16),
                    itemCount: detail.messages.length,
                    itemBuilder: (context, index) => _MessageBubble(
                      message: detail.messages[index],
                      showMeta: _showMeta(detail.messages, index),
                    ),
                  ),
                ),
                if (_errorMessage != null)
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Text(
                      _errorMessage!,
                      style: const TextStyle(color: AppColors.statusRejected, fontSize: 12.5),
                    ),
                  ),
                if (detail.thread.status == 'closed')
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
                    child: Text(
                      'เรื่องนี้ปิดแล้ว การส่งข้อความจะเปิดเรื่องขึ้นมาใหม่อัตโนมัติ',
                      style: TextStyle(fontSize: 11.5, color: tokens.textSecondary),
                      textAlign: TextAlign.center,
                    ),
                  ),
                SafeArea(
                  top: false,
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        if (_attachmentPath != null)
                          Padding(
                            padding: const EdgeInsets.only(bottom: 8),
                            child: AttachmentPreview(
                              path: _attachmentPath!,
                              onClear: () => setState(() => _attachmentPath = null),
                            ),
                          ),
                        Row(
                          spacing: 10,
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            IconButton(
                              onPressed: _sending ? null : _pickAttachment,
                              icon: const Icon(Icons.attach_file),
                            ),
                            Expanded(
                              child: TextField(
                                controller: _bodyController,
                                decoration: const InputDecoration(hintText: 'พิมพ์ข้อความตอบกลับ'),
                                minLines: 1,
                                maxLines: 4,
                              ),
                            ),
                            IconButton.filled(
                              onPressed: _sending ? null : _send,
                              icon: _sending
                                  ? const SizedBox(
                                      width: 16,
                                      height: 16,
                                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                    )
                                  : const Icon(Icons.send),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
    );
  }
}

/// Facebook-style grouping: an avatar/timestamp only needs to show once
/// per run of consecutive messages from the same sender sent within 5
/// minutes of each other — shown on the *last* message of that run, same
/// rule as the web chat's showMeta() in contact/show.blade.php. (Sender
/// name isn't shown inline at all, matching Facebook — the avatar plus
/// whichever side the bubble's on is enough context.)
bool _showMeta(List<ContactMessage> messages, int index) {
  final current = messages[index];
  if (index == messages.length - 1) return true;
  final next = messages[index + 1];
  if (next.isMine != current.isMine || next.senderName != current.senderName) return true;
  return next.createdAt.difference(current.createdAt).inSeconds > 300;
}

final _urlPattern = RegExp(r'https?://\S+');
final _trailingPunctuation = RegExp(r'[).,!?;:]+$');

/// Splits a message body into plain-text spans plus tappable link spans —
/// see contact/show.blade.php's linkify() for the same http(s)-only,
/// trim-trailing-punctuation rule, mirrored here since Flutter's Text
/// widget has no HTML equivalent to render into.
TextSpan _linkify(String text, TextStyle baseStyle, Color linkColor) {
  final spans = <TextSpan>[];
  var start = 0;

  for (final match in _urlPattern.allMatches(text)) {
    if (match.start > start) {
      spans.add(TextSpan(text: text.substring(start, match.start)));
    }

    var url = match.group(0)!;
    final trailingMatch = _trailingPunctuation.firstMatch(url);
    final trailing = trailingMatch?.group(0) ?? '';
    if (trailing.isNotEmpty) {
      url = url.substring(0, url.length - trailing.length);
    }

    spans.add(TextSpan(
      text: url,
      style: TextStyle(color: linkColor, decoration: TextDecoration.underline),
      recognizer: TapGestureRecognizer()..onTap = () => launchUrl(Uri.parse(url)),
    ));
    if (trailing.isNotEmpty) spans.add(TextSpan(text: trailing));

    start = match.end;
  }

  if (start < text.length) {
    spans.add(TextSpan(text: text.substring(start)));
  }

  return TextSpan(style: baseStyle, children: spans);
}

class _MessageBubble extends StatelessWidget {
  const _MessageBubble({required this.message, required this.showMeta});

  final ContactMessage message;

  /// Whether to show the avatar/name/timestamp for this message, per
  /// _showMeta() above.
  final bool showMeta;

  @override
  Widget build(BuildContext context) {
    final tokens = context.surfaceColors;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final hasBody = message.body != null && message.body!.isNotEmpty;
    final imageOnly = message.attachmentUrl != null && message.isImageAttachment && !hasBody;

    return Padding(
      // Grouped (non-last) messages in a run sit tighter together than the
      // last bubble of a group, matching the web chat's visual grouping.
      padding: EdgeInsets.only(bottom: showMeta ? 12 : 3),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.end,
        mainAxisAlignment: message.isMine ? MainAxisAlignment.end : MainAxisAlignment.start,
        children: [
          if (!message.isMine) ...[
            // maintainSize keeps the 28px column reserved even when hidden,
            // so grouped bubbles stay aligned instead of drifting left.
            Visibility(
              visible: showMeta,
              maintainSize: true,
              maintainAnimation: true,
              maintainState: true,
              child: CircleAvatar(
                radius: 14,
                backgroundColor: AppColors.purple500,
                backgroundImage: message.senderAvatar != null ? NetworkImage(message.senderAvatar!) : null,
                child: message.senderAvatar == null
                    ? Text(
                        message.senderName.isNotEmpty ? message.senderName[0] : '?',
                        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Colors.white),
                      )
                    : null,
              ),
            ),
            const SizedBox(width: 8),
          ],
          Flexible(
            child: Column(
              crossAxisAlignment: message.isMine ? CrossAxisAlignment.end : CrossAxisAlignment.start,
              children: [
                Container(
                  padding: imageOnly ? EdgeInsets.zero : const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                  clipBehavior: Clip.antiAlias,
                  decoration: BoxDecoration(
                    gradient: message.isMine
                        ? const LinearGradient(colors: [AppColors.purple600, AppColors.purple500])
                        : null,
                    color: message.isMine ? null : (isDark ? tokens.surface : Colors.white),
                    border: message.isMine ? null : Border.all(color: tokens.border),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (message.attachmentUrl != null && message.isImageAttachment)
                        GestureDetector(
                          onTap: () => Navigator.of(context).push(
                            MaterialPageRoute(builder: (_) => _ImageViewer(url: message.attachmentUrl!)),
                          ),
                          child: Padding(
                            padding: imageOnly ? EdgeInsets.zero : const EdgeInsets.only(bottom: 8),
                            child: ClipRRect(
                              borderRadius: imageOnly ? BorderRadius.circular(16) : BorderRadius.circular(10),
                              child: ConstrainedBox(
                                constraints: const BoxConstraints(maxHeight: 220),
                                child: Image.network(
                                  message.attachmentUrl!,
                                  fit: BoxFit.cover,
                                ),
                              ),
                            ),
                          ),
                        ),
                      if (message.attachmentUrl != null && !message.isImageAttachment)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 8),
                          child: InkWell(
                            borderRadius: BorderRadius.circular(10),
                            onTap: () => launchUrl(Uri.parse(message.attachmentUrl!)),
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                              decoration: BoxDecoration(
                                color: message.isMine
                                    ? Colors.white.withValues(alpha: 0.15)
                                    : (isDark ? tokens.scaffoldBg : AppColors.purple50),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Icon(
                                    Icons.insert_drive_file_outlined,
                                    size: 18,
                                    color: message.isMine ? Colors.white : tokens.textPrimary,
                                  ),
                                  const SizedBox(width: 6),
                                  Flexible(
                                    child: Text(
                                      message.attachmentName ?? 'ไฟล์แนบ',
                                      overflow: TextOverflow.ellipsis,
                                      style: TextStyle(
                                        fontSize: 12.5,
                                        fontWeight: FontWeight.w600,
                                        color: message.isMine ? Colors.white : tokens.textPrimary,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      if (hasBody)
                        Text.rich(
                          _linkify(
                            message.body!,
                            TextStyle(
                              fontSize: 13.5,
                              color: message.isMine ? Colors.white : tokens.textPrimary,
                            ),
                            message.isMine ? Colors.white : AppColors.purple600,
                          ),
                        ),
                    ],
                  ),
                ),
                if (showMeta)
                  Padding(
                    padding: const EdgeInsets.only(top: 3, left: 4, right: 4),
                    child: Text(
                      '${message.createdAt.day}/${message.createdAt.month}/${message.createdAt.year} '
                      '${message.createdAt.hour.toString().padLeft(2, '0')}:${message.createdAt.minute.toString().padLeft(2, '0')}',
                      style: TextStyle(fontSize: 10.5, color: tokens.textSecondary),
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// Full-screen tap-to-view for an image attachment — no external viewer
/// package needed, just an interactive-zoom Image.network on a black
/// background with a close button and a download action (the web viewer's
/// download link — see Student\ContactController's contact/show.blade.php
/// — has no equivalent here otherwise, since Image.network doesn't get a
/// native "save image" long-press menu the way a browser <img> does).
class _ImageViewer extends StatefulWidget {
  const _ImageViewer({required this.url});

  final String url;

  @override
  State<_ImageViewer> createState() => _ImageViewerState();
}

class _ImageViewerState extends State<_ImageViewer> {
  bool _downloading = false;

  Future<void> _download() async {
    setState(() => _downloading = true);
    try {
      final response = await Dio().get<List<int>>(
        widget.url,
        options: Options(responseType: ResponseType.bytes),
      );
      final name = widget.url.split('/').last.split('?').first;
      final ext = name.contains('.') ? name.split('.').last : 'jpg';
      final path = await FileSaver.instance.saveAs(
        name: name.contains('.') ? name.substring(0, name.lastIndexOf('.')) : name,
        bytes: Uint8List.fromList(response.data!),
        fileExtension: ext,
        mimeType: MimeType.other,
      );
      if (!mounted) return;
      if (path != null) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('บันทึกรูปภาพลงเครื่องสำเร็จ')));
      }
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('บันทึกรูปภาพไม่สำเร็จ กรุณาลองใหม่อีกครั้ง')));
    } finally {
      if (mounted) setState(() => _downloading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        actions: [
          IconButton(
            icon: _downloading
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                  )
                : const Icon(Icons.download_outlined),
            tooltip: 'ดาวน์โหลด',
            onPressed: _downloading ? null : _download,
          ),
        ],
      ),
      body: Center(
        child: InteractiveViewer(
          child: Image.network(widget.url),
        ),
      ),
    );
  }
}
