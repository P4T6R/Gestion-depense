import 'package:flutter/material.dart';
import '../api.dart';
import '../models.dart';
import '../theme.dart';
import '../widgets.dart';

class HomeScreen extends StatefulWidget {
  final ApiClient api;
  final AppUser user;
  final Chantier site;
  final List<Chantier> sites;
  final ValueChanged<Chantier> onSiteChanged;
  final VoidCallback onScan;
  final VoidCallback onViewAll;
  final ValueChanged<Expense> onExpense;
  const HomeScreen({super.key, required this.api, required this.user, required this.site, required this.sites, required this.onSiteChanged, required this.onScan, required this.onViewAll, required this.onExpense});
  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  ExpensePage? _page;
  Map<String, dynamic>? _stats;
  Object? _error;
  @override
  void initState() { super.initState(); _load(); }
  Future<void> _load() async {
    try {
      final results = await Future.wait<Object>([widget.api.expenses(chantierId: widget.site.id), widget.api.expenseStats(widget.site.id)]);
      if (mounted) setState(() { _page = results[0] as ExpensePage; _stats = results[1] as Map<String, dynamic>; _error = null; });
    } catch (error) { if (mounted) setState(() => _error = error); }
  }
  void _chooseSite() {
    showModalBottomSheet<void>(context: context, showDragHandle: true, builder: (context) => SafeArea(child: Padding(padding: const EdgeInsets.fromLTRB(20, 8, 20, 24), child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text('Choisir un chantier', style: Theme.of(context).textTheme.titleLarge), const SizedBox(height: 14),
      ...widget.sites.map((site) => ListTile(contentPadding: EdgeInsets.zero, leading: const Icon(Icons.apartment_rounded, color: Brand.teal), title: Text(site.name), subtitle: site.location.isEmpty ? null : Text(site.location), trailing: site.id == widget.site.id ? const Icon(Icons.check_rounded, color: Brand.teal) : null, onTap: () { Navigator.pop(context); widget.onSiteChanged(site); })),
    ]))));
  }
  @override
  Widget build(BuildContext context) => SafeArea(child: RefreshIndicator(onRefresh: _load, child: CustomScrollView(physics: const AlwaysScrollableScrollPhysics(), slivers: [
    SliverPadding(padding: const EdgeInsets.fromLTRB(20, 22, 20, 28), sliver: SliverList(delegate: SliverChildListDelegate([
      Row(children: [Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text('Bonjour ${widget.user.firstName}', style: Theme.of(context).textTheme.headlineMedium),
        const SizedBox(height: 3), const Text('Bienvenue sur votre espace terrain', style: TextStyle(color: Color(0xFF66777A))),
      ])), Container(width: 48, height: 48, decoration: BoxDecoration(color: Brand.mint, borderRadius: BorderRadius.circular(16)), child: const Icon(Icons.engineering_rounded, color: Brand.teal))]),
      const SizedBox(height: 24),
      Card(child: InkWell(onTap: _chooseSite, borderRadius: BorderRadius.circular(20), child: Padding(padding: const EdgeInsets.all(17), child: Row(children: [
        Container(width: 44, height: 44, decoration: BoxDecoration(color: Brand.mint, borderRadius: BorderRadius.circular(13)), child: const Icon(Icons.location_city_rounded, color: Brand.teal)),
        const SizedBox(width: 12), Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [const Text('CHANTIER ACTUEL', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, letterSpacing: 1, color: Color(0xFF66777A))), const SizedBox(height: 3), Text(widget.site.name, style: Theme.of(context).textTheme.titleMedium), if (widget.site.location.isNotEmpty) Text(widget.site.location, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 12, color: Color(0xFF66777A)))])),
        const Icon(Icons.expand_more_rounded, color: Brand.ink),
      ])))),
      const SizedBox(height: 24),
      Container(padding: const EdgeInsets.all(22), decoration: BoxDecoration(color: Brand.tealDark, borderRadius: BorderRadius.circular(24)), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        const Icon(Icons.document_scanner_outlined, color: Color(0xFFBFE6DD), size: 28), const SizedBox(height: 18),
        const Text('Une facture à envoyer ?', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 23)),
        const SizedBox(height: 5), const Text('Photographiez-la. Vérifiez les informations. C’est envoyé.', style: TextStyle(color: Color(0xFFDAEFEB), height: 1.4)),
        const SizedBox(height: 19), FilledButton.icon(onPressed: widget.onScan, icon: const Icon(Icons.camera_alt_outlined), label: const Text('Scanner une facture'), style: FilledButton.styleFrom(backgroundColor: Colors.white, foregroundColor: Brand.tealDark)),
      ])),
      const SizedBox(height: 28),
      SectionTitle('Vue rapide', trailing: TextButton(onPressed: widget.onViewAll, child: const Text('Voir tout'))),
      const SizedBox(height: 12),
      if (_error != null) Card(child: Padding(padding: const EdgeInsets.all(15), child: Row(children: [const Expanded(child: Text('Données indisponibles.')), TextButton(onPressed: _load, child: const Text('Réessayer'))])))
      else if (_stats == null) const Center(child: Padding(padding: EdgeInsets.all(20), child: CircularProgressIndicator()))
      else Row(children: [
        Expanded(child: _Metric(value: '${_stats!['total_depenses'] ?? 0}', label: 'Factures', icon: Icons.receipt_long_outlined)),
        const SizedBox(width: 10), Expanded(child: _Metric(value: '${_stats!['rejetees'] ?? 0}', label: 'À corriger', icon: Icons.edit_note_rounded, attention: true)),
        const SizedBox(width: 10), Expanded(child: _Metric(value: '${(_stats!['en_attente'] as num? ?? 0) + (_stats!['acceptees'] as num? ?? 0)}', label: 'En cours', icon: Icons.schedule_rounded)),
      ]),
      const SizedBox(height: 29), SectionTitle('Factures récentes', trailing: TextButton(onPressed: widget.onViewAll, child: const Text('Voir tout'))), const SizedBox(height: 12),
      if (_page != null && _page!.items.isEmpty) const Card(child: Padding(padding: EdgeInsets.all(22), child: Text('Aucune facture pour ce chantier. Scannez la première pour commencer.'))),
      if (_page != null) ..._page!.items.take(3).map((expense) => Padding(padding: const EdgeInsets.only(bottom: 9), child: ExpenseTile(expense: expense, onTap: () => widget.onExpense(expense)))),
    ]))),
  ])));
}

class _Metric extends StatelessWidget {
  final String value;
  final String label;
  final IconData icon;
  final bool attention;
  const _Metric({required this.value, required this.label, required this.icon, this.attention = false});
  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 16),
    decoration: BoxDecoration(color: attention ? Brand.amberSurface : Colors.white, borderRadius: BorderRadius.circular(18), border: Border.all(color: attention ? const Color(0xFFF2DEBE) : Brand.outline)),
    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Icon(icon, size: 23, color: attention ? Brand.amber : Brand.teal), const SizedBox(height: 15), Text(value, style: TextStyle(fontSize: 25, fontWeight: FontWeight.w800, color: attention ? Brand.amber : Brand.ink)), const SizedBox(height: 2), Text(label, maxLines: 1, style: const TextStyle(fontSize: 12, color: Color(0xFF66777A)))]),
  );
}
