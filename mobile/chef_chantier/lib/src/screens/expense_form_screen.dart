import 'dart:io';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:image_picker/image_picker.dart';

import '../api.dart';
import '../models.dart';
import '../ocr_parser.dart';
import '../theme.dart';
import '../widgets.dart';

class ExpenseFormScreen extends StatefulWidget {
  final ApiClient api;
  final Chantier site;
  final String? imagePath;
  final InvoiceExtraction? extraction;
  final Expense? correction;
  const ExpenseFormScreen({super.key, required this.api, required this.site, this.imagePath, this.extraction, this.correction});
  @override
  State<ExpenseFormScreen> createState() => _ExpenseFormScreenState();
}

class _ExpenseFormScreenState extends State<ExpenseFormScreen> {
  final _form = GlobalKey<FormState>();
  late final TextEditingController _supplier;
  late final TextEditingController _number;
  late final TextEditingController _dateText;
  late final TextEditingController _amount;
  late final TextEditingController _description;
  late final TextEditingController _notes;
  late final List<_EditableLine> _lines;
  DateTime? _date;
  List<Choice>? _categories;
  List<Choice>? _units;
  int? _categoryId;
  int? _unitId;
  bool _delivered = false;
  bool _sending = false;
  String? _newImagePath;
  Object? _choicesError;

  @override
  void initState() {
    super.initState();
    final old = widget.correction;
    final extraction = widget.extraction;
    _supplier = TextEditingController(text: old?.supplier ?? extraction?.supplier ?? '');
    _number = TextEditingController(text: old?.invoiceNumber ?? extraction?.number ?? '');
    _date = old?.invoiceDate == null ? extraction?.date : DateTime.tryParse(old!.invoiceDate!);
    _dateText = TextEditingController(text: _date == null ? '' : DateFormat('dd/MM/yyyy').format(_date!));
    _amount = TextEditingController(text: old?.amount ?? extraction?.amount ?? '');
    _description = TextEditingController(text: old?.description ?? '');
    _notes = TextEditingController(text: old?.observations ?? '');
    _lines = old?.lines.map((line) => _EditableLine(line)).toList() ?? [];
    _categoryId = old?.categoryId;
    _unitId = old?.unitId;
    _delivered = old?.deliveryStatus == 'livre';
    _loadChoices();
  }
  @override
  void dispose() { for (final c in [_supplier, _number, _dateText, _amount, _description, _notes]) { c.dispose(); } for (final line in _lines) { line.dispose(); } super.dispose(); }
  Future<void> _loadChoices() async {
    try {
      final result = await Future.wait<List<Choice>>([widget.api.categories(), widget.api.units()]);
      if (mounted) setState(() { _categories = result[0]; _units = result[1]; _choicesError = null; });
    } catch (error) { if (mounted) setState(() => _choicesError = error); }
  }
  Future<void> _pickDate() async {
    final date = await showDatePicker(context: context, initialDate: _date ?? DateTime.now(), firstDate: DateTime(2000), lastDate: DateTime.now().add(const Duration(days: 365)));
    if (date != null) setState(() { _date = date; _dateText.text = DateFormat('dd/MM/yyyy').format(date); });
  }
  Future<void> _addPhoto(ImageSource source) async {
    try {
      final image = await ImagePicker().pickImage(source: source, imageQuality: 85, maxWidth: 2200);
      if (image == null) return;
      if (await image.length() > 5 * 1024 * 1024) throw const ApiException('La photo dépasse 5 Mo.', 0);
      if (mounted) setState(() => _newImagePath = image.path);
    } catch (error) { if (mounted) showError(context, error); }
  }
  String? _required(String? value) => value == null || value.trim().isEmpty ? 'Ce champ est obligatoire.' : null;
  String? _amountValid(String? input) {
    final value = input?.trim().replaceAll(RegExp(r'[\s\u00a0\u202f]'), '').replaceAll(',', '.') ?? '';
    if (!RegExp(r'^\d{1,13}(?:\.\d{1,2})?$').hasMatch(value) || (double.tryParse(value) ?? 0) <= 0) return 'Saisissez un montant positif, avec 2 décimales maximum.';
    return null;
  }
  String? _lineNumber(String? input, {bool zeroAllowed = false, double max = 9999999999999.99}) {
    final value = input?.trim().replaceAll(',', '.') ?? '';
    final number = double.tryParse(value);
    if (!RegExp(r'^\d{1,13}(?:\.\d{1,2})?$').hasMatch(value) || number == null || number > max || (zeroAllowed ? number < 0 : number <= 0)) return 'Nombre invalide.';
    return null;
  }
  Future<void> _submit() async {
    if (!_form.currentState!.validate()) return;
    if (_date == null || (_lines.isEmpty && (_categoryId == null || _unitId == null))) { showError(context, 'Complétez la date, la catégorie et l’unité.'); return; }
    setState(() => _sending = true);
    try {
      final amount = _amount.text.trim().replaceAll(RegExp(r'[\s\u00a0\u202f]'), '').replaceAll(',', '.');
      final fields = <String, String>{
        'chantier_id': '${widget.site.id}', 'fournisseur': _supplier.text.trim(), 'numero_facture': _number.text.trim(),
        'date_facture': DateFormat('yyyy-MM-dd').format(_date!),
        if (_lines.isEmpty) ...{'nom_produit': _description.text.trim(), 'quantite': '1', 'prix_unitaire': amount, 'categorie_id': '$_categoryId', 'unite_id': '$_unitId'},
        'statut_livraison': _delivered ? 'livre' : 'non_livre',
        if (widget.correction == null) 'statut_paiement': 'non_paye',
        if (_notes.text.trim().isNotEmpty || widget.correction != null) 'observations': _notes.text.trim(),
      };
      for (var index = 0; index < _lines.length; index++) {
        final line = _lines[index];
        fields['lignes[$index][designation]'] = line.description.text.trim();
        fields['lignes[$index][quantite]'] = line.quantity.text.trim().replaceAll(',', '.');
        fields['lignes[$index][prix_unitaire]'] = line.unitPrice.text.trim().replaceAll(',', '.');
        if (line.categoryId != null) fields['lignes[$index][categorie_depense_id]'] = '${line.categoryId}';
        if (line.unitId != null) fields['lignes[$index][unite_mesure_id]'] = '${line.unitId}';
      }
      final created = await widget.api.submitExpense(correctionId: widget.correction?.id, fields: fields, imagePath: _newImagePath ?? widget.imagePath);
      if (mounted) Navigator.pop(context, created);
    } catch (error) { if (mounted) showError(context, error); }
    finally { if (mounted) setState(() => _sending = false); }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: Text(widget.correction == null ? 'Vérifier la facture' : 'Corriger la facture')),
    body: SafeArea(child: Column(children: [
      Expanded(child: ListView(padding: const EdgeInsets.fromLTRB(20, 10, 20, 20), children: [
        if (widget.correction != null) Container(padding: const EdgeInsets.all(16), decoration: BoxDecoration(color: Brand.amberSurface, borderRadius: BorderRadius.circular(16)), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [const Text('Motif du rejet', style: TextStyle(fontWeight: FontWeight.w800, color: Brand.amber)), const SizedBox(height: 5), Text(widget.correction!.rejectionReason ?? 'Vérifiez les informations.', style: const TextStyle(color: Brand.ink))])),
        if (widget.correction != null) const SizedBox(height: 18),
        if (widget.imagePath != null) ClipRRect(borderRadius: BorderRadius.circular(18), child: SizedBox(height: 145, child: Image.file(File(widget.imagePath!), fit: BoxFit.cover, width: double.infinity, errorBuilder: (_, error, stack) => const Icon(Icons.broken_image_outlined)))),
        if (widget.imagePath != null) const SizedBox(height: 12),
        if (widget.correction != null) ...[
          if (_newImagePath != null) ClipRRect(borderRadius: BorderRadius.circular(18), child: SizedBox(height: 130, width: double.infinity, child: Image.file(File(_newImagePath!), fit: BoxFit.cover))),
          OutlinedButton.icon(onPressed: () => _addPhoto(ImageSource.camera), icon: const Icon(Icons.camera_alt_outlined), label: const Text('Ajouter une nouvelle photo')),
          TextButton.icon(onPressed: () => _addPhoto(ImageSource.gallery), icon: const Icon(Icons.photo_library_outlined), label: const Text('Choisir dans la galerie')),
          const SizedBox(height: 12),
        ],
        Container(padding: const EdgeInsets.all(14), decoration: BoxDecoration(color: Brand.mint, borderRadius: BorderRadius.circular(16)), child: const Row(children: [Icon(Icons.auto_awesome_outlined, color: Brand.teal), SizedBox(width: 10), Expanded(child: Text('Les données lues sur la photo sont des propositions. Vérifiez-les avant l’envoi.', style: TextStyle(fontSize: 13, color: Brand.ink)))])),
        const SizedBox(height: 25), Text('Informations de la facture', style: Theme.of(context).textTheme.titleLarge), const SizedBox(height: 15),
        Form(key: _form, child: Column(children: [
          TextFormField(controller: _supplier, textCapitalization: TextCapitalization.words, decoration: const InputDecoration(labelText: 'Fournisseur *', prefixIcon: Icon(Icons.storefront_outlined)), validator: _required), const SizedBox(height: 13),
          TextFormField(controller: _number, decoration: const InputDecoration(labelText: 'Numéro de facture *', prefixIcon: Icon(Icons.tag_rounded)), validator: _required), const SizedBox(height: 13),
          TextFormField(controller: _dateText, readOnly: true, onTap: _pickDate, decoration: const InputDecoration(labelText: 'Date de facture *', prefixIcon: Icon(Icons.calendar_today_outlined), suffixIcon: Icon(Icons.chevron_right_rounded)), validator: (_) => _date == null ? 'Choisissez la date de facture.' : null), const SizedBox(height: 13),
          if (_lines.isEmpty) ...[TextFormField(controller: _amount, keyboardType: const TextInputType.numberWithOptions(decimal: true), decoration: const InputDecoration(labelText: 'Montant total TTC *', suffixText: 'FCFA', prefixIcon: Icon(Icons.payments_outlined)), validator: _amountValid), const SizedBox(height: 13)],
          TextFormField(initialValue: widget.site.name, enabled: false, decoration: const InputDecoration(labelText: 'Chantier', prefixIcon: Icon(Icons.apartment_rounded))), const SizedBox(height: 24),
          Align(alignment: Alignment.centerLeft, child: Text('Classement', style: Theme.of(context).textTheme.titleLarge)), const SizedBox(height: 13),
          if (_lines.isEmpty) ...[TextFormField(controller: _description, textCapitalization: TextCapitalization.sentences, decoration: const InputDecoration(labelText: 'Objet de la dépense *', hintText: 'Ex. Achat de ciment', prefixIcon: Icon(Icons.inventory_2_outlined)), validator: _required), const SizedBox(height: 13)],
          if (_choicesError != null) ListTile(title: const Text('Catégories indisponibles'), trailing: TextButton(onPressed: _loadChoices, child: const Text('Réessayer')))
          else if (_categories == null || _units == null) const Padding(padding: EdgeInsets.all(20), child: CircularProgressIndicator())
          else if (_lines.isEmpty) ...[
            DropdownButtonFormField<int>(value: _categories!.any((c) => c.id == _categoryId) ? _categoryId : null, isExpanded: true, decoration: const InputDecoration(labelText: 'Catégorie *', prefixIcon: Icon(Icons.category_outlined)), items: _categories!.map((c) => DropdownMenuItem(value: c.id, child: Text(c.name, overflow: TextOverflow.ellipsis))).toList(), onChanged: (value) => setState(() => _categoryId = value), validator: (value) => value == null ? 'Choisissez une catégorie.' : null),
            const SizedBox(height: 13),
            DropdownButtonFormField<int>(value: _units!.any((u) => u.id == _unitId) ? _unitId : null, isExpanded: true, decoration: const InputDecoration(labelText: 'Unité *', prefixIcon: Icon(Icons.straighten_rounded)), items: _units!.map((u) => DropdownMenuItem(value: u.id, child: Text(u.symbol == null ? u.name : '${u.name} (${u.symbol})', overflow: TextOverflow.ellipsis))).toList(), onChanged: (value) => setState(() => _unitId = value), validator: (value) => value == null ? 'Choisissez une unité.' : null),
          ],
          if (_lines.isNotEmpty && _categories != null && _units != null) ...[
            for (var index = 0; index < _lines.length; index++) Padding(padding: const EdgeInsets.only(bottom: 12), child: Card(child: Padding(padding: const EdgeInsets.all(15), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Ligne ${index + 1}', style: Theme.of(context).textTheme.titleMedium), const SizedBox(height: 12),
              TextFormField(controller: _lines[index].description, decoration: const InputDecoration(labelText: 'Désignation *'), validator: _required), const SizedBox(height: 9),
              LayoutBuilder(builder: (context, constraints) {
                final quantity = TextFormField(controller: _lines[index].quantity, keyboardType: const TextInputType.numberWithOptions(decimal: true), decoration: const InputDecoration(labelText: 'Quantité *'), validator: (value) => _lineNumber(value, max: 99999999.99));
                final unitPrice = TextFormField(controller: _lines[index].unitPrice, keyboardType: const TextInputType.numberWithOptions(decimal: true), decoration: const InputDecoration(labelText: 'Prix unitaire *'), validator: (value) => _lineNumber(value, zeroAllowed: true));
                if (constraints.maxWidth < 320 || MediaQuery.textScalerOf(context).scale(1) > 1.2) {
                  return Column(children: [quantity, const SizedBox(height: 9), unitPrice]);
                }
                return Row(children: [Expanded(child: quantity), const SizedBox(width: 9), Expanded(child: unitPrice)]);
              }),
              const SizedBox(height: 9),
              DropdownButtonFormField<int>(value: _categories!.any((c) => c.id == _lines[index].categoryId) ? _lines[index].categoryId : null, isExpanded: true, decoration: const InputDecoration(labelText: 'Catégorie'), items: _categories!.map((c) => DropdownMenuItem(value: c.id, child: Text(c.name, overflow: TextOverflow.ellipsis))).toList(), onChanged: (value) => _lines[index].categoryId = value),
              const SizedBox(height: 9),
              DropdownButtonFormField<int>(value: _units!.any((u) => u.id == _lines[index].unitId) ? _lines[index].unitId : null, isExpanded: true, decoration: const InputDecoration(labelText: 'Unité'), items: _units!.map((u) => DropdownMenuItem(value: u.id, child: Text(u.name, overflow: TextOverflow.ellipsis))).toList(), onChanged: (value) => _lines[index].unitId = value),
            ])))),
          ],
          const SizedBox(height: 13), TextFormField(controller: _notes, maxLines: 2, decoration: const InputDecoration(labelText: 'Note facultative', alignLabelWithHint: true)),
          const SizedBox(height: 8), SwitchListTile(contentPadding: EdgeInsets.zero, title: const Text('Matériaux déjà livrés'), subtitle: const Text('Indiquez seulement si la livraison a eu lieu.'), value: _delivered, onChanged: (value) => setState(() => _delivered = value)),
        ])),
      ])),
      Padding(padding: const EdgeInsets.fromLTRB(20, 10, 20, 16), child: FilledButton.icon(onPressed: _sending || _categories == null || _units == null ? null : _submit, icon: _sending ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Icon(Icons.send_rounded), label: Text(widget.correction == null ? 'Soumettre la facture' : 'Corriger et renvoyer'))),
    ])),
  );
}

class _EditableLine {
  final TextEditingController description;
  final TextEditingController quantity;
  final TextEditingController unitPrice;
  int? categoryId;
  int? unitId;
  _EditableLine(ExpenseLine line)
      : description = TextEditingController(text: line.description),
        quantity = TextEditingController(text: line.quantity),
        unitPrice = TextEditingController(text: line.unitPrice),
        categoryId = line.categoryId,
        unitId = line.unitId;
  void dispose() { description.dispose(); quantity.dispose(); unitPrice.dispose(); }
}
