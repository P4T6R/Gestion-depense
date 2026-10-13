import 'package:flutter/material.dart';

import '../api.dart';
import '../models.dart';
import '../theme.dart';
import '../widgets.dart';
import 'capture_screen.dart';
import 'expense_detail_screen.dart';
import 'expenses_screen.dart';
import 'home_screen.dart';

class ShellScreen extends StatefulWidget {
  final ApiClient api;
  final AppUser user;
  final Future<void> Function() onLogout;
  const ShellScreen({super.key, required this.api, required this.user, required this.onLogout});
  @override
  State<ShellScreen> createState() => _ShellScreenState();
}

class _ShellScreenState extends State<ShellScreen> {
  List<Chantier>? _sites;
  Chantier? _selected;
  Object? _error;
  int _tab = 0;
  int _revision = 0;

  @override
  void initState() { super.initState(); _loadSites(); }
  Future<void> _loadSites() async {
    try {
      final sites = await widget.api.chantiers();
      if (mounted) setState(() { _sites = sites; _selected = sites.where((s) => s.id == _selected?.id).firstOrNull ?? sites.firstOrNull; _error = null; });
    } catch (error) { if (mounted) setState(() => _error = error); }
  }
  void _refresh() => setState(() => _revision++);
  Future<void> _openCapture() async {
    if (_selected == null) return;
    final created = await Navigator.of(context).push<Expense>(MaterialPageRoute(builder: (_) => CaptureScreen(api: widget.api, site: _selected!)));
    if (created != null && mounted) { _refresh(); setState(() => _tab = 1); }
  }
  Future<void> _openExpense(Expense expense) async {
    await Navigator.of(context).push(MaterialPageRoute(builder: (_) => ExpenseDetailScreen(api: widget.api, site: _selected!, expenseId: expense.id, onChanged: _refresh)));
    if (mounted) _refresh();
  }

  @override
  Widget build(BuildContext context) {
    if (_sites == null) return Scaffold(body: _error == null ? const Center(child: CircularProgressIndicator()) : MessageState(icon: Icons.wifi_off_rounded, title: 'Chantiers indisponibles', message: 'Vérifiez votre connexion pour retrouver vos chantiers.', action: 'Réessayer', onAction: _loadSites));
    if (_sites!.isEmpty) return Scaffold(appBar: AppBar(title: const Text('Mes chantiers'), actions: [IconButton(tooltip: 'Se déconnecter', onPressed: widget.onLogout, icon: const Icon(Icons.logout_rounded))]), body: MessageState(icon: Icons.apartment_rounded, title: 'Aucun chantier attribué', message: 'Contactez votre administrateur pour être affecté à un chantier.', action: 'Actualiser', onAction: _loadSites));
    final selected = _selected!;
    return Scaffold(
      body: IndexedStack(index: _tab, children: [
        HomeScreen(key: ValueKey('home-${selected.id}-$_revision'), api: widget.api, user: widget.user, site: selected, sites: _sites!, onSiteChanged: (site) => setState(() => _selected = site), onScan: _openCapture, onViewAll: () => setState(() => _tab = 1), onExpense: _openExpense),
        ExpensesScreen(key: ValueKey('list-${selected.id}-$_revision'), api: widget.api, site: selected, onScan: _openCapture, onExpense: _openExpense),
        _ProfileScreen(user: widget.user, sites: _sites!, onLogout: widget.onLogout),
      ]),
      bottomNavigationBar: NavigationBar(selectedIndex: _tab, onDestinationSelected: (index) => setState(() => _tab = index), destinations: const [
        NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home_rounded), label: 'Accueil'),
        NavigationDestination(icon: Icon(Icons.receipt_long_outlined), selectedIcon: Icon(Icons.receipt_long_rounded), label: 'Factures'),
        NavigationDestination(icon: Icon(Icons.person_outline_rounded), selectedIcon: Icon(Icons.person_rounded), label: 'Profil'),
      ]),
    );
  }
}

class _ProfileScreen extends StatelessWidget {
  final AppUser user;
  final List<Chantier> sites;
  final Future<void> Function() onLogout;
  const _ProfileScreen({required this.user, required this.sites, required this.onLogout});
  @override
  Widget build(BuildContext context) => SafeArea(child: ListView(padding: const EdgeInsets.all(20), children: [
    const SizedBox(height: 15), Text('Mon profil', style: Theme.of(context).textTheme.headlineMedium), const SizedBox(height: 28),
    Card(child: Padding(padding: const EdgeInsets.all(20), child: Row(children: [CircleAvatar(radius: 28, backgroundColor: Brand.mint, child: Text(user.firstName[0].toUpperCase(), style: const TextStyle(color: Brand.teal, fontSize: 22, fontWeight: FontWeight.w800))), const SizedBox(width: 14), Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(user.name, style: Theme.of(context).textTheme.titleMedium), const SizedBox(height: 3), const Text('Chef de chantier', style: TextStyle(color: Color(0xFF66777A)))]))]))),
    const SizedBox(height: 20), Card(child: Column(children: [ListTile(leading: const Icon(Icons.alternate_email_rounded), title: const Text('Adresse e-mail'), subtitle: Text(user.email)), const Divider(height: 1), ListTile(leading: const Icon(Icons.apartment_rounded), title: const Text('Chantiers attribués'), subtitle: Text('${sites.length} chantier${sites.length > 1 ? 's' : ''}'))])),
    const SizedBox(height: 24), OutlinedButton.icon(onPressed: () async { await onLogout(); }, icon: const Icon(Icons.logout_rounded), label: const Text('Se déconnecter')),
  ]));
}
