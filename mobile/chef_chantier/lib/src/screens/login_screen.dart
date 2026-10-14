import 'package:flutter/material.dart';
import '../theme.dart';
import '../widgets.dart';

class LoginScreen extends StatefulWidget {
  final Future<void> Function(String, String) onLogin;
  const LoginScreen({super.key, required this.onLogin});
  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _busy = false;
  bool _obscure = true;
  @override
  void dispose() { _email.dispose(); _password.dispose(); super.dispose(); }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _busy = true);
    try { await widget.onLogin(_email.text.trim(), _password.text); }
    catch (error) { if (mounted) showError(context, error); }
    finally { if (mounted) setState(() => _busy = false); }
  }

  @override
  Widget build(BuildContext context) => Scaffold(body: SafeArea(child: Center(child: SingleChildScrollView(padding: const EdgeInsets.all(24), child: ConstrainedBox(constraints: const BoxConstraints(maxWidth: 420), child: AutofillGroup(child: Form(key: _formKey, child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
    Container(width: 64, height: 64, decoration: BoxDecoration(color: Brand.teal, borderRadius: BorderRadius.circular(20)), child: const Icon(Icons.apartment_rounded, color: Colors.white, size: 33)),
    const SizedBox(height: 42),
    Text('Le terrain,\nen toute simplicité.', style: Theme.of(context).textTheme.headlineLarge?.copyWith(height: 1.15)),
    const SizedBox(height: 12),
    const Text('Vos factures et leur suivi, directement depuis votre chantier.', style: TextStyle(fontSize: 16, color: Color(0xFF66777A), height: 1.5)),
    const SizedBox(height: 35),
    TextFormField(controller: _email, keyboardType: TextInputType.emailAddress, autofillHints: const [AutofillHints.username, AutofillHints.email], textInputAction: TextInputAction.next,
      decoration: const InputDecoration(labelText: 'Adresse e-mail', prefixIcon: Icon(Icons.alternate_email_rounded)),
      validator: (value) => value == null || !value.contains('@') ? 'Saisissez votre adresse e-mail.' : null),
    const SizedBox(height: 14),
    TextFormField(controller: _password, obscureText: _obscure, autofillHints: const [AutofillHints.password], onFieldSubmitted: (_) => _submit(),
      decoration: InputDecoration(labelText: 'Mot de passe', prefixIcon: const Icon(Icons.lock_outline_rounded), suffixIcon: IconButton(tooltip: _obscure ? 'Afficher le mot de passe' : 'Masquer le mot de passe', icon: Icon(_obscure ? Icons.visibility_outlined : Icons.visibility_off_outlined), onPressed: () => setState(() => _obscure = !_obscure))),
      validator: (value) => value == null || value.isEmpty ? 'Saisissez votre mot de passe.' : null),
    const SizedBox(height: 24),
    FilledButton(onPressed: _busy ? null : _submit, child: _busy ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Row(mainAxisAlignment: MainAxisAlignment.center, children: [Text('Se connecter'), SizedBox(width: 10), Icon(Icons.arrow_forward_rounded)])),
    const SizedBox(height: 22),
    const Center(child: Text('Accès réservé aux chefs de chantier', style: TextStyle(fontSize: 13, color: Color(0xFF66777A)))),
  ]))))))));
}
