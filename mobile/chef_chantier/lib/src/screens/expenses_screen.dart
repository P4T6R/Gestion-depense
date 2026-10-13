import 'dart:async';
import 'package:flutter/material.dart';
import '../api.dart';
import '../models.dart';
import '../theme.dart';
import '../widgets.dart';

class ExpensesScreen extends StatefulWidget {
  final ApiClient api;
  final Chantier site;
  final VoidCallback onScan;
  final ValueChanged<Expense> onExpense;
  const ExpensesScreen({super.key, required this.api, required this.site, required this.onScan, required this.onExpense});
  @override
  State<ExpensesScreen> createState() => _ExpensesScreenState();
}

class _ExpensesScreenState extends State<ExpensesScreen> {
  static const _filters = <String?, String>{null: 'Toutes', 'rejetee': 'À corriger', 'en_attente': 'Directeur', 'acceptee': 'Comptable', 'validee': 'Validées'};
  final _search = TextEditingController();
  Timer? _debounce;
  List<Expense> _items = [];
  String? _status;
  int _page = 0;
  int _lastPage = 1;
  bool _loading = false;
  int _generation = 0;
  Object? _error;
  @override
  void initState() { super.initState(); _reload(); }
  @override
  void dispose() { _debounce?.cancel(); _search.dispose(); super.dispose(); }
  Future<void> _reload() async {
    setState(() { _generation++; _page = 0; _lastPage = 1; _items = []; _loading = false; _error = null; });
    await _loadMore();
  }
  Future<void> _loadMore() async {
    if (_loading || _page >= _lastPage) return;
    final generation = _generation;
    setState(() => _loading = true);
    try {
      final result = await widget.api.expenses(page: _page + 1, chantierId: widget.site.id, status: _status, search: _search.text.trim());
      if (mounted && generation == _generation) setState(() { _items.addAll(result.items); _page = result.currentPage; _lastPage = result.lastPage; _error = null; });
    } catch (error) { if (mounted && generation == _generation) setState(() => _error = error); }
    finally { if (mounted && generation == _generation) setState(() => _loading = false); }
  }
  @override
  Widget build(BuildContext context) => SafeArea(child: Stack(children: [
    RefreshIndicator(onRefresh: _reload, child: CustomScrollView(physics: const AlwaysScrollableScrollPhysics(), slivers: [
      SliverPadding(padding: const EdgeInsets.fromLTRB(20, 20, 20, 0), sliver: SliverList(delegate: SliverChildListDelegate([
        Text('Mes factures', style: Theme.of(context).textTheme.headlineMedium),
        const SizedBox(height: 4), Text(widget.site.name, style: const TextStyle(color: Color(0xFF66777A))),
        const SizedBox(height: 22),
        TextField(controller: _search, onChanged: (_) { _debounce?.cancel(); _debounce = Timer(const Duration(milliseconds: 380), _reload); }, decoration: const InputDecoration(hintText: 'Fournisseur, numéro, objet…', prefixIcon: Icon(Icons.search_rounded))),
        const SizedBox(height: 14),
        SizedBox(height: 42, child: ListView(scrollDirection: Axis.horizontal, children: _filters.entries.map((entry) => Padding(padding: const EdgeInsets.only(right: 8), child: ChoiceChip(label: Text(entry.value), selected: _status == entry.key, onSelected: (_) { setState(() => _status = entry.key); _reload(); }))).toList())),
        const SizedBox(height: 18),
      ]))),
      if (_items.isEmpty && _loading) const SliverFillRemaining(child: Center(child: CircularProgressIndicator()))
      else if (_items.isEmpty && _error != null) SliverFillRemaining(child: MessageState(icon: Icons.wifi_off_rounded, title: 'Factures indisponibles', message: 'La liste ne peut pas être chargée pour le moment.', action: 'Réessayer', onAction: _reload))
      else if (_items.isEmpty) SliverFillRemaining(child: MessageState(icon: Icons.receipt_long_outlined, title: 'Aucune facture ici', message: _search.text.isEmpty ? 'Scannez une facture pour commencer le suivi de ce chantier.' : 'Essayez un autre mot-clé ou un autre filtre.'))
      else SliverPadding(padding: const EdgeInsets.fromLTRB(20, 0, 20, 110), sliver: SliverList.builder(itemCount: _items.length + (_page < _lastPage ? 1 : 0), itemBuilder: (context, index) {
        if (index == _items.length) return Padding(padding: const EdgeInsets.symmetric(vertical: 15), child: OutlinedButton(onPressed: _loading ? null : _loadMore, child: Text(_error != null ? 'Réessayer' : 'Charger davantage')));
        final expense = _items[index];
        return Padding(padding: const EdgeInsets.only(bottom: 9), child: ExpenseTile(expense: expense, onTap: () => widget.onExpense(expense)));
      })),
    ])),
    Positioned(left: 20, right: 20, bottom: 18, child: FilledButton.icon(onPressed: widget.onScan, icon: const Icon(Icons.document_scanner_outlined), label: const Text('Scanner une facture'))),
  ]));
}
