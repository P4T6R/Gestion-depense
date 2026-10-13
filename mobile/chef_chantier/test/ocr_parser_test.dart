import 'package:flutter_test/flutter_test.dart';
import 'package:gestion_depenses_terrain/src/ocr_parser.dart';

void main() {
  test('extracts labelled invoice fields and TTC rather than HT', () {
    final result = InvoiceParser.parse('''
CIMENT IVOIRE
FACTURE FAC-2026-042
Date : 13/10/2026
TOTAL HT 207 627
TVA 37 373
TOTAL TTC 245 000 FCFA
''');
    expect(result.supplier, 'CIMENT IVOIRE');
    expect(result.number, 'FAC-2026-042');
    expect(result.date, DateTime(2026, 10, 13));
    expect(result.amount, '245000.00');
  });
  test('leaves uncertain total and invalid date for manual entry', () {
    final result = InvoiceParser.parse('Boutique chantier\n31/02/2026\nCiment 50 000');
    expect(result.date, isNull);
    expect(result.amount, isNull);
  });
  test('prefers the invoice date over another printed date', () {
    final result = InvoiceParser.parse('Entreprise créée le 01/01/2010\nDate facture : 13/10/2026\nTOTAL TTC 245000');
    expect(result.date, DateTime(2026, 10, 13));
  });
}
