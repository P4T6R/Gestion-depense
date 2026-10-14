import 'package:intl/intl.dart';

int asId(Object? value) => value is int ? value : int.tryParse('$value') ?? 0;
String asText(Object? value) => value?.toString() ?? '';
Map<String, dynamic> asMap(Object? value) =>
    value is Map ? Map<String, dynamic>.from(value) : <String, dynamic>{};

double asAmount(Object? value) =>
    double.tryParse(asText(value).replaceAll(' ', '').replaceAll(',', '.')) ?? 0;

String money(Object? value) {
  final amount = asAmount(value);
  final format = NumberFormat.decimalPattern('fr');
  return '${format.format(amount)} FCFA';
}

String shortDate(String? value) {
  if (value == null || value.isEmpty) return 'Date inconnue';
  final date = DateTime.tryParse(value);
  return date == null ? value : DateFormat('dd/MM/yyyy').format(date);
}

class AppUser {
  final int id;
  final String name;
  final String email;
  final String role;

  const AppUser({required this.id, required this.name, required this.email, required this.role});
  factory AppUser.fromJson(Object? json) {
    final map = asMap(json);
    return AppUser(id: asId(map['id']), name: asText(map['name']), email: asText(map['email']), role: asText(map['role']));
  }

  String get firstName => name.trim().isEmpty ? 'Chef' : name.trim().split(RegExp(r'\s+')).first;
}

class Chantier {
  final int id;
  final String name;
  final String location;
  final String status;
  const Chantier({required this.id, required this.name, required this.location, required this.status});
  factory Chantier.fromJson(Object? json) {
    final map = asMap(json);
    return Chantier(id: asId(map['id']), name: asText(map['nom']), location: asText(map['lieu']), status: asText(map['statut']));
  }
}

class Choice {
  final int id;
  final String name;
  final String? symbol;
  const Choice({required this.id, required this.name, this.symbol});
  factory Choice.fromJson(Object? json) {
    final map = asMap(json);
    return Choice(id: asId(map['id']), name: asText(map['nom']), symbol: map['symbole']?.toString());
  }
}

class Attachment {
  final int id;
  final String name;
  final String mime;
  const Attachment({required this.id, required this.name, required this.mime});
  factory Attachment.fromJson(Object? json) {
    final map = asMap(json);
    return Attachment(id: asId(map['id']), name: asText(map['nom_fichier']), mime: asText(map['type_fichier']));
  }
}

class ExpenseLine {
  final String description;
  final String quantity;
  final String unitPrice;
  final int? categoryId;
  final int? unitId;
  const ExpenseLine({required this.description, required this.quantity, required this.unitPrice, this.categoryId, this.unitId});
  factory ExpenseLine.fromJson(Object? json) {
    final map = asMap(json);
    return ExpenseLine(description: asText(map['designation']), quantity: asText(map['quantite']), unitPrice: asText(map['prix_unitaire']), categoryId: map['categorie_depense_id'] == null ? null : asId(map['categorie_depense_id']), unitId: map['unite_mesure_id'] == null ? null : asId(map['unite_mesure_id']));
  }
}

class ExpenseEvent {
  final String action;
  final String actor;
  final String? reason;
  final String date;
  const ExpenseEvent({required this.action, required this.actor, required this.reason, required this.date});
  factory ExpenseEvent.fromJson(Object? json) {
    final map = asMap(json);
    return ExpenseEvent(action: asText(map['action']), actor: asText(map['acteur_nom']), reason: map['motif']?.toString(), date: asText(map['created_at']));
  }
  String get label => switch (action) {
    'creation' => 'Facture envoyée',
    'modification' => 'Facture modifiée',
    'resoumission' => 'Facture renvoyée',
    'approbation' || 'approve' => 'Approuvée par le directeur',
    'validation' || 'validate' => 'Validée par la comptable',
    'rejet' || 'reject' => 'Correction demandée',
    'paiement' || 'reprise_paiement' => 'Paiement enregistré',
    'annulation_paiement' => 'Paiement annulé',
    'etat_initial' => 'État initial importé',
    _ => 'Décision enregistrée',
  };
}

class Expense {
  final int id;
  final int chantierId;
  final String supplier;
  final String invoiceNumber;
  final String? invoiceDate;
  final String description;
  final String observations;
  final String amount;
  final String status;
  final String deliveryStatus;
  final String? rejectionReason;
  final int? categoryId;
  final int? unitId;
  final List<Attachment> attachments;
  final List<ExpenseLine> lines;
  final List<ExpenseEvent> history;

  const Expense({required this.id, required this.chantierId, required this.supplier, required this.invoiceNumber, required this.invoiceDate, required this.description, required this.observations, required this.amount, required this.status, required this.deliveryStatus, required this.rejectionReason, required this.categoryId, required this.unitId, required this.attachments, required this.lines, required this.history});
  factory Expense.fromJson(Object? json) {
    final map = asMap(json);
    final files = map['justificatifs'];
    final lines = map['lignes'];
    final history = map['historique'];
    return Expense(
      id: asId(map['id']), chantierId: asId(map['chantier_id']), supplier: asText(map['fournisseur']),
      invoiceNumber: asText(map['numero_facture']), invoiceDate: map['date_facture']?.toString(),
      description: asText(map['nom_produit']), observations: asText(map['observations']), amount: asText(map['prix_total']), status: asText(map['statut_validation']), deliveryStatus: asText(map['statut_livraison']),
      rejectionReason: map['motif_rejet']?.toString(), categoryId: map['categorie_id'] == null ? null : asId(map['categorie_id']),
      unitId: map['unite_id'] == null ? null : asId(map['unite_id']),
      attachments: files is List ? files.map((item) => Attachment.fromJson(item)).toList() : [],
      lines: lines is List ? lines.map((item) => ExpenseLine.fromJson(item)).toList() : [],
      history: history is List ? history.map((item) => ExpenseEvent.fromJson(item)).toList() : [],
    );
  }
}

class ExpensePage {
  final List<Expense> items;
  final int currentPage;
  final int lastPage;
  const ExpensePage(this.items, this.currentPage, this.lastPage);
  factory ExpensePage.fromJson(Object? json) {
    final map = asMap(json);
    final raw = map['data'];
    return ExpensePage(raw is List ? raw.map((e) => Expense.fromJson(e)).toList() : [], asId(map['current_page']), asId(map['last_page']));
  }
}

String statusLabel(String status) => switch (status) {
  'validee' => 'Validée',
  'acceptee' => 'Chez la comptable',
  'rejetee' => 'À corriger',
  _ => 'Chez le directeur',
};
