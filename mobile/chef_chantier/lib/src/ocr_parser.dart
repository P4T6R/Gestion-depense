/// Values proposed by OCR are deliberately nullable: guessing a wrong invoice
/// number or total would be more costly than asking for manual input.
class InvoiceExtraction {
  final String? supplier;
  final String? number;
  final DateTime? date;
  final String? amount;
  final String rawText;
  const InvoiceExtraction({this.supplier, this.number, this.date, this.amount, this.rawText = ''});
}

class InvoiceParser {
  static final _date = RegExp(r'\b(\d{1,2})[/.\-](\d{1,2})[/.\-](\d{4})\b');
  static final _isoDate = RegExp(r'\b(\d{4})-(\d{2})-(\d{2})\b');
  static final _number = RegExp(r'(?:N[°ºo]?\s*(?:de\s*)?(?:facture\s*)?|FACTURE\s*(?:N[°ºo]?\s*)?)[\s:#-]*([A-Z0-9][A-Z0-9/\-]{3,})', caseSensitive: false);
  static final _money = RegExp(r'(?<!\d)(?:\d{1,3}(?:[ .\u00a0\u202f]\d{3})+|\d{4,})(?:[,.]\d{1,2})?(?!\d)');
  static final _total = RegExp(r'(?:TOTAL\s*(?:TTC|A\s*PAYER|À\s*PAYER)?|NET\s*(?:A|À)\s*PAYER|MONTANT\s*TTC)', caseSensitive: false);

  static InvoiceExtraction parse(String text) {
    final lines = text.split(RegExp(r'\r?\n')).map((line) => line.trim().replaceAll(RegExp(r'\s+'), ' ')).where((line) => line.isNotEmpty).toList();
    final normalized = lines.map(_fold).toList();
    String? supplier;
    for (var index = 0; index < lines.length && index < 6; index++) {
      final line = lines[index];
      final folded = normalized[index];
      if (line.length >= 3 && RegExp(r'[A-Za-zÀ-ÿ]').hasMatch(line) && !RegExp(r'FACTURE|RECU|REÇU|DEVIS|DATE|NUMERO|TOTAL|TEL|RCCM|TVA|CLIENT|ADRESSE').hasMatch(folded) && !RegExp(r'^\d').hasMatch(line)) {
        supplier = line;
        break;
      }
    }
    String? number;
    for (final line in lines) {
      final match = _number.firstMatch(line);
      if (match != null && !RegExp(r'^\d{4}$').hasMatch(match.group(1)!)) { number = match.group(1)!.toUpperCase(); break; }
    }
    DateTime? date;
    final dateLines = <String>[
      ...lines.where((line) => RegExp(r'\bDATE\b', caseSensitive: false).hasMatch(line)),
      ...lines.where((line) => !RegExp(r'\bDATE\b', caseSensitive: false).hasMatch(line)),
    ];
    for (final line in dateLines) {
      final iso = _isoDate.firstMatch(line);
      final normal = _date.firstMatch(line);
      if (iso != null) { date = _validDate(int.parse(iso.group(1)!), int.parse(iso.group(2)!), int.parse(iso.group(3)!)); }
      else if (normal != null) { date = _validDate(int.parse(normal.group(3)!), int.parse(normal.group(2)!), int.parse(normal.group(1)!)); }
      if (date != null) break;
    }
    String? amount;
    // Only a labelled total is trusted. HT, tax and line item amounts are ignored.
    for (var index = lines.length - 1; index >= 0; index--) {
      if (!_total.hasMatch(normalized[index]) || RegExp(r'\bHT\b|SOUS.TOTAL').hasMatch(normalized[index])) continue;
      final tail = lines[index].replaceFirst(_total, '');
      final sameLine = _money.allMatches(tail).toList();
      final nextLine = index + 1 < lines.length ? _money.allMatches(lines[index + 1]).toList() : <RegExpMatch>[];
      final value = sameLine.isNotEmpty ? sameLine.last.group(0) : nextLine.isNotEmpty ? nextLine.first.group(0) : null;
      amount = _normaliseAmount(value);
      if (amount != null) break;
    }
    return InvoiceExtraction(supplier: supplier, number: number, date: date, amount: amount, rawText: text);
  }

  static String _fold(String text) => text.toUpperCase().replaceAll('É', 'E').replaceAll('È', 'E').replaceAll('Ê', 'E').replaceAll('À', 'A');
  static DateTime? _validDate(int year, int month, int day) {
    if (year < 2000 || year > 2100 || month < 1 || month > 12 || day < 1 || day > 31) return null;
    final value = DateTime(year, month, day);
    return value.year == year && value.month == month && value.day == day ? value : null;
  }
  static String? _normaliseAmount(String? text) {
    if (text == null) return null;
    final cleaned = text.replaceAll(RegExp(r'[\s\u00a0\u202f]'), '').replaceAll(',', '.');
    if (cleaned.contains('.') && RegExp(r'\.\d{3}$').hasMatch(cleaned)) return cleaned.replaceAll('.', '');
    final value = double.tryParse(cleaned);
    return value == null || value <= 0 ? null : value.toStringAsFixed(2);
  }
}
