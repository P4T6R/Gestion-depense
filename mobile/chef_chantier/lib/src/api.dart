import 'dart:convert';
import 'dart:typed_data';

import 'package:http/http.dart' as http;

import 'config.dart';
import 'models.dart';

class ApiException implements Exception {
  final String message;
  final int status;
  final Map<String, dynamic> errors;
  const ApiException(this.message, this.status, [this.errors = const {}]);
  @override
  String toString() => message;
}

class ApiClient {
  final http.Client _http;
  String? token;

  ApiClient({http.Client? client}) : _http = client ?? http.Client();

  Uri _uri(String path, [Map<String, String>? query]) => Uri.parse('${AppConfig.apiBaseUrl}/api$path').replace(queryParameters: query);
  Map<String, String> get _headers => {
    'Accept': 'application/json',
    if (token != null) 'Authorization': 'Bearer $token',
  };

  Future<dynamic> _request(String method, String path, [Map<String, dynamic>? body, Map<String, String>? query]) async {
    final uri = _uri(path, query);
    late http.Response response;
    try {
      response = await _http.send(http.Request(method, uri)
        ..headers.addAll({..._headers, if (body != null) 'Content-Type': 'application/json'})
        ..body = body == null ? '' : jsonEncode(body)).then(http.Response.fromStream).timeout(const Duration(seconds: 25));
    } catch (_) {
      throw const ApiException('Connexion impossible. Vérifiez votre réseau et réessayez.', 0);
    }
    return _decode(response);
  }

  dynamic _decode(http.Response response) {
    dynamic json;
    try { json = jsonDecode(utf8.decode(response.bodyBytes)); } catch (_) { json = null; }
    if (response.statusCode >= 400) {
      final data = asMap(json);
      final errors = asMap(data['errors']);
      final firstError = errors.values.whereType<List>().expand((list) => list).firstOrNull;
      throw ApiException(asText(firstError ?? data['message']).isNotEmpty ? asText(firstError ?? data['message']) : 'Une erreur est survenue.', response.statusCode, errors);
    }
    return json;
  }

  Future<(AppUser, String)> login(String email, String password) async {
    final data = asMap(await _request('POST', '/mobile/login', {'email': email, 'password': password}));
    return (AppUser.fromJson(data['user']), asText(data['token']));
  }
  Future<AppUser> me() async => AppUser.fromJson(asMap(await _request('GET', '/auth/me'))['user']);
  Future<void> logout() async => _request('POST', '/auth/logout');
  Future<List<Chantier>> chantiers() async {
    final data = await _request('GET', '/chantiers');
    return data is List ? data.map((item) => Chantier.fromJson(item)).toList() : [];
  }
  Future<List<Choice>> categories() async {
    final data = await _request('GET', '/categories', null, {'actives_only': '1'});
    return data is List ? data.where((item) => asMap(item)['type'] == 'chantier').map((item) => Choice.fromJson(item)).toList() : [];
  }
  Future<List<Choice>> units() async {
    final data = await _request('GET', '/unites', null, {'actives_only': '1'});
    return data is List ? data.map((item) => Choice.fromJson(item)).toList() : [];
  }
  Future<ExpensePage> expenses({int page = 1, int? chantierId, String? status, String? search}) async {
    return ExpensePage.fromJson(await _request('GET', '/depenses', null, {
      'page': '$page', if (chantierId != null) 'chantier_id': '$chantierId', if (status != null) 'statut_validation': status, if (search != null && search.isNotEmpty) 'q': search,
    }));
  }
  Future<Map<String, dynamic>> expenseStats(int chantierId) async =>
      asMap(await _request('GET', '/depenses/statistiques', null, {'chantier_id': '$chantierId'}));
  Future<Expense> expense(int id) async => Expense.fromJson(await _request('GET', '/depenses/$id'));
  Future<Uint8List> attachment(int id) async {
    try {
      final response = await _http.get(_uri('/justificatifs/$id'), headers: _headers).timeout(const Duration(seconds: 25));
      if (response.statusCode >= 400) { _decode(response); }
      return response.bodyBytes;
    } catch (error) {
      if (error is ApiException) rethrow;
      throw const ApiException('Impossible de charger le justificatif.', 0);
    }
  }
  Future<Expense> submitExpense({int? correctionId, required Map<String, String> fields, String? imagePath}) async {
    final path = correctionId == null ? '/depenses' : '/depenses/$correctionId';
    final request = http.MultipartRequest('POST', _uri(path))..headers.addAll(_headers)..fields.addAll(fields);
    if (correctionId != null) request.fields['_method'] = 'PUT';
    if (imagePath != null) request.files.add(await http.MultipartFile.fromPath('justificatif', imagePath));
    late http.StreamedResponse stream;
    try { stream = await request.send().timeout(const Duration(seconds: 60)); }
    catch (_) { throw const ApiException('Envoi impossible. Vérifiez votre réseau et réessayez.', 0); }
    final data = asMap(_decode(await http.Response.fromStream(stream)));
    return Expense.fromJson(data['depense']);
  }
}
