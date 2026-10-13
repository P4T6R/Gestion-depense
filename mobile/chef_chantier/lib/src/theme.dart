import 'package:flutter/material.dart';

class Brand {
  static const ink = Color(0xFF142A30);
  static const teal = Color(0xFF006B63);
  static const tealDark = Color(0xFF00534D);
  static const canvas = Color(0xFFF6F7F5);
  static const outline = Color(0xFFE1E6E2);
  static const amber = Color(0xFF9A601A);
  static const amberSurface = Color(0xFFFFF2DB);
  static const mint = Color(0xFFE2F3EF);
}

ThemeData appTheme() {
  final scheme = ColorScheme.fromSeed(seedColor: Brand.teal, primary: Brand.teal, surface: Colors.white);
  return ThemeData(
    useMaterial3: true,
    colorScheme: scheme,
    scaffoldBackgroundColor: Brand.canvas,
    fontFamily: 'Roboto',
    textTheme: const TextTheme(
      headlineLarge: TextStyle(fontSize: 30, fontWeight: FontWeight.w800, letterSpacing: -0.8, color: Brand.ink),
      headlineMedium: TextStyle(fontSize: 25, fontWeight: FontWeight.w800, letterSpacing: -0.5, color: Brand.ink),
      titleLarge: TextStyle(fontSize: 20, fontWeight: FontWeight.w700, letterSpacing: -0.3, color: Brand.ink),
      titleMedium: TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: Brand.ink),
      bodyLarge: TextStyle(fontSize: 16, color: Brand.ink),
      bodyMedium: TextStyle(fontSize: 14, color: Brand.ink),
    ),
    appBarTheme: const AppBarTheme(backgroundColor: Brand.canvas, foregroundColor: Brand.ink, scrolledUnderElevation: 0, centerTitle: false),
    inputDecorationTheme: InputDecorationTheme(
      filled: true, fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 17),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: Brand.outline)),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: Brand.outline)),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: Brand.teal, width: 1.6)),
      errorBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: Colors.redAccent)),
    ),
    filledButtonTheme: FilledButtonThemeData(style: FilledButton.styleFrom(
      backgroundColor: Brand.teal, foregroundColor: Colors.white,
      minimumSize: const Size.fromHeight(54), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
    )),
    cardTheme: CardThemeData(color: Colors.white, elevation: 0, margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20), side: const BorderSide(color: Brand.outline))),
    navigationBarTheme: NavigationBarThemeData(backgroundColor: Colors.white, indicatorColor: Brand.mint,
      labelTextStyle: WidgetStateProperty.resolveWith((states) => TextStyle(color: states.contains(WidgetState.selected) ? Brand.teal : Brand.ink, fontSize: 12, fontWeight: FontWeight.w600))),
  );
}
