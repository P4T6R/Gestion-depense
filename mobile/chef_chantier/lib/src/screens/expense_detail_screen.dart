import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:pdfrx/pdfrx.dart';

import '../api.dart';
import '../models.dart';
import '../theme.dart';
import '../widgets.dart';
import 'expense_form_screen.dart';

class ExpenseDetailScreen extends StatefulWidget {
  final ApiClient api;
  final Chantier site;
  final int expenseId;
  final VoidCallback onChanged;
  const ExpenseDetailScreen({super.key, required this.api, required this.site, required this.expenseId, required this.onChanged});
  @override
  State<ExpenseDetailScreen> createState() => _ExpenseDetailScreenState();
}

class _ExpenseDetailScreenState extends State<ExpenseDetailScreen> {
  Expense? _expense;
  Object? _error;
  @override
  void initState() { super.initState(); _load(); }
  Future<void> _load() async {
    try { final expense = await widget.api.expense(widget.expenseId); if (mounted) setState(() { _expense = expense; _error = null; }); }
    catch (error) { if (mounted) setState(() => _error = error); }
  }
  Future<void> _correct() async {
    final updated = await Navigator.push<Expense>(context, MaterialPageRoute(builder: (_) => ExpenseFormScreen(api: widget.api, site: widget.site, correction: _expense)));
    if (updated != null && mounted) { widget.onChanged(); await _load(); ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Facture corrigée et renvoyée.'))); }
  }
  void _preview(Attachment attachment) {
    Navigator.push(context, MaterialPageRoute(builder: (_) => _AttachmentViewer(api: widget.api, attachment: attachment)));
  }
  @override
  Widget build(BuildContext context) {
    if (_expense == null) return Scaffold(appBar: AppBar(title: const Text('Facture')), body: _error == null ? const Center(child: CircularProgressIndicator()) : MessageState(icon: Icons.wifi_off_rounded, title: 'Facture indisponible', message: 'Impossible de charger les détails.', action: 'Réessayer', onAction: _load));
    final expense = _expense!;
    return Scaffold(appBar: AppBar(title: Text('Facture #${expense.id}')), body: SafeArea(child: Column(children: [
      Expanded(child: RefreshIndicator(onRefresh: _load, child: ListView(padding: const EdgeInsets.fromLTRB(20, 8, 20, 26), children: [
        ExpenseStatus(expense.status), const SizedBox(height: 18),
        Text(expense.supplier.isNotEmpty ? expense.supplier : expense.description.isNotEmpty ? expense.description : 'Facture #${expense.id}', style: Theme.of(context).textTheme.headlineMedium),
        const SizedBox(height: 7), Text(money(expense.amount), style: const TextStyle(fontSize: 28, fontWeight: FontWeight.w800, color: Brand.teal)),
        const SizedBox(height: 22),
        if (expense.status == 'rejetee') Container(padding: const EdgeInsets.all(17), decoration: BoxDecoration(color: Brand.amberSurface, borderRadius: BorderRadius.circular(18)), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [const Row(children: [Icon(Icons.error_outline_rounded, color: Brand.amber), SizedBox(width: 8), Text('Correction demandée', style: TextStyle(fontWeight: FontWeight.w800, color: Brand.amber))]), const SizedBox(height: 8), Text(expense.rejectionReason ?? 'Consultez la facture avant de la renvoyer.')]))
        else _Progress(status: expense.status),
        const SizedBox(height: 25), SectionTitle('Informations'), const SizedBox(height: 12),
        Card(child: Column(children: [
          _InfoRow('Numéro', expense.invoiceNumber.isEmpty ? 'Non renseigné' : expense.invoiceNumber),
          const Divider(height: 1), _InfoRow('Date', shortDate(expense.invoiceDate)),
          const Divider(height: 1), _InfoRow('Chantier', widget.site.name),
          const Divider(height: 1), _InfoRow('Objet', expense.description),
        ])),
        const SizedBox(height: 25), SectionTitle('Justificatifs'), const SizedBox(height: 12),
        if (expense.attachments.isEmpty) const Card(child: Padding(padding: EdgeInsets.all(18), child: Text('Aucun justificatif joint.'))),
        ...expense.attachments.map((file) => Padding(padding: const EdgeInsets.only(bottom: 8), child: Card(child: ListTile(leading: Icon(file.mime == 'application/pdf' ? Icons.picture_as_pdf_outlined : Icons.image_outlined, color: Brand.teal), title: Text(file.name, maxLines: 1, overflow: TextOverflow.ellipsis), subtitle: const Text('Aperçu du document'), trailing: const Icon(Icons.open_in_new_rounded), onTap: () => _preview(file))))),
        if (expense.history.isNotEmpty) ...[
          const SizedBox(height: 23), SectionTitle('Historique'), const SizedBox(height: 12),
          Card(child: Column(children: [for (var index = expense.history.length - 1; index >= 0; index--) ...[
            if (index != expense.history.length - 1) const Divider(height: 1),
            ListTile(leading: const Icon(Icons.history_rounded, color: Brand.teal), title: Text(expense.history[index].label),
              subtitle: Text('${shortDate(expense.history[index].date)}${expense.history[index].actor.isEmpty ? '' : ' · ${expense.history[index].actor}'}${expense.history[index].reason == null ? '' : '\n${expense.history[index].reason}'}')),
          ]])),
        ],
      ]))),
      if (expense.status == 'rejetee') Padding(padding: const EdgeInsets.fromLTRB(20, 10, 20, 16), child: FilledButton.icon(onPressed: _correct, icon: const Icon(Icons.edit_outlined), label: const Text('Corriger et renvoyer'))),
    ])));
  }
}

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;
  const _InfoRow(this.label, this.value);
  @override
  Widget build(BuildContext context) => Padding(padding: const EdgeInsets.symmetric(horizontal: 17, vertical: 15), child: Row(children: [SizedBox(width: 95, child: Text(label, style: const TextStyle(color: Color(0xFF66777A)))), Expanded(child: Text(value, textAlign: TextAlign.right, style: const TextStyle(fontWeight: FontWeight.w600)))]));
}

class _Progress extends StatelessWidget {
  final String status;
  const _Progress({required this.status});
  @override
  Widget build(BuildContext context) {
    final level = status == 'validee' ? 3 : status == 'acceptee' ? 2 : 1;
    final steps = ['Envoyée', 'Approuvée', 'Validée'];
    return Card(child: Padding(padding: const EdgeInsets.all(17), child: Row(children: [for (var i = 0; i < steps.length; i++) Expanded(child: Column(children: [Icon(i < level ? Icons.check_circle_rounded : Icons.radio_button_unchecked_rounded, color: i < level ? Brand.teal : const Color(0xFF9AA8A7), size: 22), const SizedBox(height: 6), Text(steps[i], style: TextStyle(fontSize: 12, fontWeight: i < level ? FontWeight.w700 : FontWeight.w400, color: Brand.ink))]))])));
  }
}

class _AttachmentViewer extends StatelessWidget {
  final ApiClient api;
  final Attachment attachment;
  const _AttachmentViewer({required this.api, required this.attachment});
  @override
  Widget build(BuildContext context) => Scaffold(appBar: AppBar(title: Text(attachment.name, maxLines: 1, overflow: TextOverflow.ellipsis)), body: FutureBuilder<Uint8List>(future: api.attachment(attachment.id), builder: (context, snapshot) {
    if (snapshot.hasError) return MessageState(icon: Icons.wifi_off_rounded, title: 'Aperçu indisponible', message: snapshot.error.toString());
    if (!snapshot.hasData) return const Center(child: CircularProgressIndicator());
    if (attachment.mime == 'application/pdf') return PdfViewer.data(snapshot.data!, sourceName: attachment.name);
    return InteractiveViewer(minScale: 0.8, maxScale: 5, child: Center(child: Image.memory(snapshot.data!, fit: BoxFit.contain)));
  }));
}
