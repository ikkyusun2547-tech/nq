import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api_exception.dart';
import '../../core/models/contact.dart';
import '../../core/providers.dart';
import '../../core/theme.dart';
import 'attachment_picker.dart';

class ContactCreateScreen extends ConsumerStatefulWidget {
  const ContactCreateScreen({
    super.key,
    this.prefilledSubject,
    this.contextType,
    this.contextId,
  });

  final String? prefilledSubject;
  final String? contextType;
  final int? contextId;

  @override
  ConsumerState<ContactCreateScreen> createState() => _ContactCreateScreenState();
}

class _ContactCreateScreenState extends ConsumerState<ContactCreateScreen> {
  late final _subjectController = TextEditingController(text: widget.prefilledSubject);
  final _bodyController = TextEditingController();
  bool _submitting = false;
  bool _sent = false;
  String? _errorMessage;

  // Only fetched/shown when this screen is opened fresh (no prefilled
  // subject/context) — a FeedRow tap already knows exactly what the
  // student is asking about, so the picker would be redundant there.
  List<ContactTopic>? _topics;
  int _selectedTopicIndex = -1;
  String? _attachmentPath;

  @override
  void initState() {
    super.initState();
    if (widget.prefilledSubject == null) {
      _loadTopics();
    }
  }

  Future<void> _loadTopics() async {
    try {
      final topics = await ref.read(contactRepositoryProvider).fetchTopics();
      if (mounted) setState(() => _topics = topics);
    } catch (_) {
      // A student with nothing flagged/rejected simply gets no picker —
      // not worth surfacing an error for.
    }
  }

  void _selectTopic(int? index) {
    setState(() => _selectedTopicIndex = index ?? -1);
    if (index == null || index < 0) return;

    final topic = _topics![index];
    if (_subjectController.text.trim().isEmpty) {
      _subjectController.text = topic.reason != null ? '${topic.title} — ${topic.reason}' : topic.title;
    }
  }

  @override
  void dispose() {
    _subjectController.dispose();
    _bodyController.dispose();
    super.dispose();
  }

  Future<void> _pickAttachment() async {
    final path = await pickAttachment(context);
    if (path != null) setState(() => _attachmentPath = path);
  }

  Future<void> _submit() async {
    final subject = _subjectController.text.trim();
    final body = _bodyController.text.trim();
    if (subject.isEmpty || (body.isEmpty && _attachmentPath == null)) {
      setState(() => _errorMessage = 'กรุณากรอกหัวข้อ และข้อความหรือไฟล์แนบ');
      return;
    }

    final selectedTopic = _selectedTopicIndex >= 0 ? _topics![_selectedTopicIndex] : null;

    setState(() {
      _submitting = true;
      _errorMessage = null;
    });

    try {
      await ref.read(contactRepositoryProvider).createThread(
            subject: subject,
            body: body.isEmpty ? null : body,
            contextType: selectedTopic?.type ?? widget.contextType,
            contextId: selectedTopic?.activityId ?? widget.contextId,
            attachmentPath: _attachmentPath,
          );
      setState(() => _sent = true);
    } on DioException catch (e) {
      setState(() => _errorMessage = e.asApiException.message);
    } catch (_) {
      setState(() => _errorMessage = 'ส่งข้อความไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      appBar: AppBar(title: const Text('เริ่มข้อความใหม่')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: _sent ? _buildSuccess() : _buildForm(isDark),
      ),
    );
  }

  Widget _buildSuccess() {
    return _buildCard(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 72,
            height: 72,
            decoration: BoxDecoration(
              color: AppColors.green50,
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.check_circle, size: 40, color: AppColors.green600),
          ),
          const SizedBox(height: 16),
          const Text('ส่งข้อความสำเร็จ รอเจ้าหน้าที่ตอบกลับ', textAlign: TextAlign.center),
          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity,
            child: FilledButton(
              onPressed: () => Navigator.of(context).pop(true),
              child: const Text('เสร็จสิ้น'),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildForm(bool isDark) {
    return _buildCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        spacing: 16,
        children: [
          Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: isDark ? AppColors.purple900.withValues(alpha: 0.4) : AppColors.purple50,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  Icons.chat_bubble_outline,
                  color: isDark ? AppColors.purple400 : AppColors.purple700,
                ),
              ),
              const SizedBox(width: 12),
              const Expanded(
                child: Text(
                  'ข้อความถึงเจ้าหน้าที่',
                  style: TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
                ),
              ),
            ],
          ),
          if (_topics != null && _topics!.isNotEmpty)
            DropdownButtonFormField<int>(
              initialValue: _selectedTopicIndex,
              decoration: const InputDecoration(labelText: 'เรื่องที่ต้องการติดต่อ'),
              items: [
                const DropdownMenuItem(value: -1, child: Text('เรื่องทั่วไป')),
                ..._topics!.asMap().entries.map(
                  (entry) => DropdownMenuItem(
                    value: entry.key,
                    child: Text(
                      entry.value.reason != null
                          ? '${entry.value.title} — ${entry.value.reason}'
                          : entry.value.title,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ),
              ],
              onChanged: _selectTopic,
            ),
          TextField(
            controller: _subjectController,
            decoration: const InputDecoration(labelText: 'หัวข้อ'),
          ),
          TextField(
            controller: _bodyController,
            decoration: const InputDecoration(labelText: 'ข้อความ (หรือแนบไฟล์/รูปภาพอย่างเดียวก็ได้)'),
            minLines: 4,
            maxLines: 8,
          ),
          if (_attachmentPath != null)
            AttachmentPreview(
              path: _attachmentPath!,
              onClear: () => setState(() => _attachmentPath = null),
            ),
          OutlinedButton.icon(
            onPressed: _pickAttachment,
            icon: const Icon(Icons.attach_file),
            label: Text(_attachmentPath == null ? 'แนบไฟล์' : 'เปลี่ยนไฟล์แนบ'),
          ),
          if (_errorMessage != null)
            Text(
              _errorMessage!,
              style: const TextStyle(color: AppColors.statusRejected, fontSize: 13),
            ),
          FilledButton.icon(
            onPressed: _submitting ? null : _submit,
            icon: _submitting
                ? const SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                  )
                : const Icon(Icons.send_outlined),
            label: Text(_submitting ? 'กำลังส่ง...' : 'ส่งข้อความ'),
          ),
        ],
      ),
    );
  }

  Widget _buildCard({required Widget child}) {
    final tokens = context.surfaceColors;

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: tokens.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: tokens.border),
      ),
      child: child,
    );
  }
}
