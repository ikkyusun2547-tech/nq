import 'dart:io';

import 'package:file_selector/file_selector.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../../core/theme.dart';

enum _AttachmentSource { camera, gallery, file }

const _imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp'];

bool isImagePath(String path) => _imageExtensions.any((ext) => path.toLowerCase().endsWith(ext));

/// Shared by contact_create_screen.dart and contact_thread_screen.dart — a
/// bottom sheet offering camera / gallery / a general document, unlike
/// hour_requests_screen.dart's private PDF-only equivalent (chat
/// attachments aren't a specific "proof", so the file option accepts the
/// same broader mime list the web chat's `<input accept>` does).
Future<String?> pickAttachment(BuildContext context) async {
  final choice = await showModalBottomSheet<_AttachmentSource>(
    context: context,
    showDragHandle: true,
    builder: (context) => SafeArea(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          ListTile(
            leading: const Icon(Icons.camera_alt_outlined),
            title: const Text('ถ่ายรูป'),
            onTap: () => Navigator.of(context).pop(_AttachmentSource.camera),
          ),
          ListTile(
            leading: const Icon(Icons.photo_library_outlined),
            title: const Text('เลือกรูปจากคลังภาพ'),
            onTap: () => Navigator.of(context).pop(_AttachmentSource.gallery),
          ),
          ListTile(
            leading: const Icon(Icons.attach_file),
            title: const Text('แนบไฟล์'),
            onTap: () => Navigator.of(context).pop(_AttachmentSource.file),
          ),
        ],
      ),
    ),
  );
  if (choice == null) return null;

  if (choice == _AttachmentSource.file) {
    const docTypes = XTypeGroup(
      label: 'เอกสาร',
      extensions: ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'],
    );
    final file = await openFile(acceptedTypeGroups: [docTypes]);
    return file?.path;
  }

  final photo = await ImagePicker().pickImage(
    source: choice == _AttachmentSource.camera ? ImageSource.camera : ImageSource.gallery,
    imageQuality: 80,
    maxWidth: 1280,
  );
  return photo?.path;
}

/// A picked-but-not-yet-sent attachment, shown above the composer —
/// thumbnail for an image, a file chip (icon + name) otherwise. Mirrors
/// hour_requests_screen.dart's `_ProofPreview`, generalized beyond PDF.
class AttachmentPreview extends StatelessWidget {
  const AttachmentPreview({super.key, required this.path, this.onClear});

  final String path;
  final VoidCallback? onClear;

  @override
  Widget build(BuildContext context) {
    final tokens = context.surfaceColors;
    final name = path.split(Platform.pathSeparator).last;

    return Container(
      padding: const EdgeInsets.all(8),
      decoration: BoxDecoration(
        color: tokens.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: tokens.border),
      ),
      child: Row(
        children: [
          if (isImagePath(path))
            ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: Image.file(File(path), width: 40, height: 40, fit: BoxFit.cover),
            )
          else
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(color: tokens.scaffoldBg, borderRadius: BorderRadius.circular(8)),
              child: Icon(Icons.insert_drive_file_outlined, size: 20, color: tokens.textSecondary),
            ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              name,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600),
            ),
          ),
          if (onClear != null)
            IconButton(
              icon: const Icon(Icons.close, size: 18),
              onPressed: onClear,
              visualDensity: VisualDensity.compact,
            ),
        ],
      ),
    );
  }
}
