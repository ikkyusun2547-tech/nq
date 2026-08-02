import 'dart:io';

import 'package:dio/dio.dart';

import '../../core/api_client.dart';
import '../../core/models/contact.dart';

class ContactRepository {
  ContactRepository({required this.apiClient});

  final ApiClient apiClient;

  Future<List<ContactThread>> fetchThreads() async {
    final response = await apiClient.dio.get('/contact');
    final items = ((response.data as Map<String, dynamic>)['data'] as List)
        .cast<Map<String, dynamic>>();

    return items.map(ContactThread.fromJson).toList();
  }

  Future<ContactThreadDetail> fetchThread(int id) async {
    final response = await apiClient.dio.get('/contact/$id');
    final data = response.data as Map<String, dynamic>;

    return ContactThreadDetail(
      thread: ContactThread.fromJson(data['thread'] as Map<String, dynamic>),
      messages: (data['messages'] as List)
          .cast<Map<String, dynamic>>()
          .map(ContactMessage.fromJson)
          .toList(),
    );
  }

  Future<ContactThread> createThread({
    required String subject,
    String? body,
    String? contextType,
    int? contextId,
    String? attachmentPath,
  }) async {
    final response = await apiClient.dio.post(
      '/contact',
      data: FormData.fromMap({
        'subject': subject,
        'body': ?body,
        'context_type': ?contextType,
        'context_id': ?contextId,
        ...await _attachmentField(attachmentPath),
      }),
    );
    final data = response.data as Map<String, dynamic>;

    return ContactThread.fromJson(data['thread'] as Map<String, dynamic>);
  }

  Future<void> reply(int threadId, {String? body, String? attachmentPath}) async {
    await apiClient.dio.post(
      '/contact/$threadId/messages',
      data: FormData.fromMap({
        'body': ?body,
        ...await _attachmentField(attachmentPath),
      }),
    );
  }

  Future<List<ContactTopic>> fetchTopics() async {
    final response = await apiClient.dio.get('/contact/topics');
    final items = ((response.data as Map<String, dynamic>)['data'] as List)
        .cast<Map<String, dynamic>>();

    return items.map(ContactTopic.fromJson).toList();
  }

  Future<ContactInfo> fetchOfficeInfo() async {
    final response = await apiClient.dio.get('/contact/info');
    return ContactInfo.fromJson((response.data as Map<String, dynamic>)['data'] as Map<String, dynamic>);
  }

  Future<Map<String, dynamic>> _attachmentField(String? attachmentPath) async {
    if (attachmentPath == null) return {};

    return {
      'attachment': await MultipartFile.fromFile(
        attachmentPath,
        filename: attachmentPath.split(Platform.pathSeparator).last,
      ),
    };
  }
}
