import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:mobile/main.dart';

void main() {
  testWidgets('app boots and shows a loading indicator while resolving the session', (
    WidgetTester tester,
  ) async {
    await tester.pumpWidget(const ProviderScope(child: SrruActivityApp()));

    expect(find.byType(CircularProgressIndicator), findsOneWidget);
  });
}
