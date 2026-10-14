import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:gestion_depenses_terrain/src/api.dart';
import 'package:gestion_depenses_terrain/src/models.dart';
import 'package:gestion_depenses_terrain/src/screens/capture_screen.dart';
import 'package:gestion_depenses_terrain/src/theme.dart';
import 'package:gestion_depenses_terrain/src/widgets.dart';

void main() {
  testWidgets('invoice tile stays readable on a narrow screen with large text', (tester) async {
    tester.view.physicalSize = const Size(320, 640);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);

    const expense = Expense(
      id: 42, chantierId: 1, supplier: 'Fournisseur de matériaux de construction',
      invoiceNumber: 'FAC-2026-042', invoiceDate: '2026-10-13',
      description: 'Achat de ciment', observations: '', amount: '245000',
      status: 'en_attente', deliveryStatus: 'non_livre', rejectionReason: null,
      categoryId: null, unitId: null, attachments: [], lines: [], history: [],
    );
    await tester.pumpWidget(MaterialApp(
      theme: appTheme(),
      builder: (context, child) => MediaQuery(
        data: MediaQuery.of(context).copyWith(textScaler: const TextScaler.linear(1.6)),
        child: child!,
      ),
      home: Scaffold(body: SafeArea(child: ExpenseTile(expense: expense, onTap: () {}))),
    ));

    expect(find.textContaining('FCFA'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });

  testWidgets('scan actions remain reachable on a short screen with large text', (tester) async {
    tester.view.physicalSize = const Size(320, 568);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);

    await tester.pumpWidget(MaterialApp(
      theme: appTheme(),
      builder: (context, child) => MediaQuery(
        data: MediaQuery.of(context).copyWith(textScaler: const TextScaler.linear(1.6)),
        child: child!,
      ),
      home: CaptureScreen(
        api: ApiClient(),
        site: const Chantier(id: 1, name: 'Riviera 3', location: 'Abidjan', status: 'actif'),
      ),
    ));

    await tester.ensureVisible(find.text('Choisir dans la galerie'));
    expect(find.text('Prendre une photo'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
}
