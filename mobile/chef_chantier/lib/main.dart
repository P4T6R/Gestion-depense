import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'src/api.dart';
import 'src/models.dart';
import 'src/screens/login_screen.dart';
import 'src/screens/shell_screen.dart';
import 'src/theme.dart';

void main() => runApp(const TerrainApp());

class TerrainApp extends StatefulWidget {
  const TerrainApp({super.key});
  @override
  State<TerrainApp> createState() => _TerrainAppState();
}

class _TerrainAppState extends State<TerrainApp> {
  final _storage = const FlutterSecureStorage();
  final _api = ApiClient();
  AppUser? _user;
  bool _loading = true;
  Object? _startupError;

  @override
  void initState() { super.initState(); _restore(); }

  Future<void> _restore() async {
    setState(() { _loading = true; _startupError = null; });
    try {
      _api.token = await _storage.read(key: 'access_token');
      if (_api.token != null) {
        final user = await _api.me();
        if (user.role != 'chef_chantier') throw const ApiException('Application réservée aux chefs de chantier.', 403);
        _user = user;
      }
    } on ApiException catch (error) {
      if (error.status == 401 || error.status == 403) {
        await _storage.delete(key: 'access_token'); _api.token = null; _user = null;
      } else { _startupError = error; }
    } catch (error) { _startupError = error; }
    if (mounted) setState(() => _loading = false);
  }

  Future<void> _login(String email, String password) async {
    final (user, token) = await _api.login(email, password);
    if (user.role != 'chef_chantier') throw const ApiException('Application réservée aux chefs de chantier.', 403);
    await _storage.write(key: 'access_token', value: token);
    _api.token = token;
    if (mounted) setState(() => _user = user);
  }

  Future<void> _logout() async {
    try { await _api.logout(); } catch (_) { /* Remove local access even if offline. */ }
    await _storage.delete(key: 'access_token');
    _api.token = null;
    if (mounted) setState(() => _user = null);
  }

  @override
  Widget build(BuildContext context) => MaterialApp(
    title: 'Gestion Dépenses Terrain', debugShowCheckedModeBanner: false,
    theme: appTheme(), locale: const Locale('fr'),
    supportedLocales: const [Locale('fr')],
    localizationsDelegates: const [GlobalMaterialLocalizations.delegate, GlobalCupertinoLocalizations.delegate, GlobalWidgetsLocalizations.delegate],
    home: _loading ? const Scaffold(body: Center(child: CircularProgressIndicator()))
      : _startupError != null ? Scaffold(body: SafeArea(child: Center(child: Column(mainAxisSize: MainAxisSize.min, children: [
          const Icon(Icons.wifi_off_rounded, size: 40, color: Brand.teal), const SizedBox(height: 16),
          const Text('Connexion momentanément indisponible'), const SizedBox(height: 12),
          FilledButton(onPressed: _restore, child: const Text('Réessayer')),
        ]))))
      : _user == null ? LoginScreen(onLogin: _login) : ShellScreen(api: _api, user: _user!, onLogout: _logout),
  );
}
