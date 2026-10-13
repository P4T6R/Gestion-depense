import 'dart:io';
import 'package:flutter/material.dart';
import 'package:google_mlkit_text_recognition/google_mlkit_text_recognition.dart';
import 'package:image_picker/image_picker.dart';

import '../api.dart';
import '../models.dart';
import '../ocr_parser.dart';
import '../theme.dart';
import '../widgets.dart';
import 'expense_form_screen.dart';

class CaptureScreen extends StatefulWidget {
  final ApiClient api;
  final Chantier site;
  const CaptureScreen({super.key, required this.api, required this.site});
  @override
  State<CaptureScreen> createState() => _CaptureScreenState();
}

class _CaptureScreenState extends State<CaptureScreen> {
  final _picker = ImagePicker();
  XFile? _image;
  InvoiceExtraction? _extraction;
  bool _working = false;
  bool _ocrFailed = false;
  @override
  void initState() { super.initState(); _recoverImage(); }
  Future<void> _recoverImage() async {
    try { final lost = await _picker.retrieveLostData(); if (lost.files?.isNotEmpty ?? false) await _prepare(lost.files!.first); }
    catch (_) { /* Manual capture remains available. */ }
  }
  Future<void> _pick(ImageSource source) async {
    try {
      final image = await _picker.pickImage(source: source, imageQuality: 85, maxWidth: 2200);
      if (image != null) await _prepare(image);
    } catch (error) { if (mounted) showError(context, error); }
  }
  Future<void> _prepare(XFile image) async {
    setState(() { _working = true; _ocrFailed = false; });
    try {
      if (await image.length() > 5 * 1024 * 1024) throw const ApiException('La photo dépasse 5 Mo. Reprenez-la avec une résolution plus faible.', 0);
      final recognizer = TextRecognizer(script: TextRecognitionScript.latin);
      try {
        final result = await recognizer.processImage(InputImage.fromFilePath(image.path));
        if (mounted) setState(() { _image = image; _extraction = InvoiceParser.parse(result.text); });
      } catch (_) {
        if (mounted) setState(() { _image = image; _extraction = const InvoiceExtraction(); _ocrFailed = true; });
      } finally { await recognizer.close(); }
    } catch (error) { if (mounted) showError(context, error); }
    finally { if (mounted) setState(() => _working = false); }
  }
  Future<void> _continue() async {
    final created = await Navigator.of(context).push<Expense>(MaterialPageRoute(builder: (_) => ExpenseFormScreen(api: widget.api, site: widget.site, imagePath: _image!.path, extraction: _extraction)));
    if (created != null && mounted) Navigator.of(context).pop(created);
  }
  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Scanner une facture')),
    body: SafeArea(child: _working ? const Center(child: Column(mainAxisSize: MainAxisSize.min, children: [CircularProgressIndicator(), SizedBox(height: 18), Text('Lecture de la facture…')]))
      : _image == null ? _empty(context) : _preview(context)),
  );

  Widget _empty(BuildContext context) => Padding(padding: const EdgeInsets.all(20), child: Column(children: [
    Expanded(child: Container(width: double.infinity, decoration: BoxDecoration(color: Brand.mint, borderRadius: BorderRadius.circular(28)), child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
      Container(padding: const EdgeInsets.all(25), decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle), child: const Icon(Icons.document_scanner_outlined, size: 52, color: Brand.teal)),
      const SizedBox(height: 27), Text('Une photo, moins de saisie.', textAlign: TextAlign.center, style: Theme.of(context).textTheme.headlineMedium),
      const SizedBox(height: 10), const Padding(padding: EdgeInsets.symmetric(horizontal: 30), child: Text('Cadrez la facture entière, avec une lumière suffisante. Vous vérifierez les données avant de l’envoyer.', textAlign: TextAlign.center, style: TextStyle(height: 1.5, color: Color(0xFF526568)))),
    ]))),
    const SizedBox(height: 20), FilledButton.icon(onPressed: () => _pick(ImageSource.camera), icon: const Icon(Icons.camera_alt_outlined), label: const Text('Prendre une photo')),
    const SizedBox(height: 8), TextButton.icon(onPressed: () => _pick(ImageSource.gallery), icon: const Icon(Icons.photo_library_outlined), label: const Text('Choisir dans la galerie')),
  ]));

  Widget _preview(BuildContext context) => Padding(padding: const EdgeInsets.all(20), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
    Text('Vérifiez la photo', style: Theme.of(context).textTheme.headlineMedium),
    const SizedBox(height: 6), const Text('Le document doit rester lisible jusqu’aux bords.', style: TextStyle(color: Color(0xFF66777A))),
    const SizedBox(height: 18), Expanded(child: ClipRRect(borderRadius: BorderRadius.circular(22), child: Container(color: const Color(0xFFE7EAE7), width: double.infinity, child: Image.file(File(_image!.path), fit: BoxFit.contain, errorBuilder: (_, _, _) => const Center(child: Icon(Icons.broken_image_outlined, size: 42)))))),
    if (_ocrFailed) const Padding(padding: EdgeInsets.only(top: 12), child: Text('Texte non reconnu. Vous pouvez remplir les champs manuellement.', style: TextStyle(color: Brand.amber))),
    const SizedBox(height: 16), FilledButton.icon(onPressed: _continue, icon: const Icon(Icons.arrow_forward_rounded), label: const Text('Vérifier les informations')),
    const SizedBox(height: 7), Center(child: TextButton(onPressed: () => _pick(ImageSource.camera), child: const Text('Reprendre la photo'))),
  ]));
}
