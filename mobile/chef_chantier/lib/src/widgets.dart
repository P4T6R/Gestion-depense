import 'package:flutter/material.dart';
import 'models.dart';
import 'theme.dart';

class SectionTitle extends StatelessWidget {
  final String title;
  final Widget? trailing;
  const SectionTitle(this.title, {super.key, this.trailing});
  @override
  Widget build(BuildContext context) => Row(children: [Expanded(child: Text(title, style: Theme.of(context).textTheme.titleLarge)), if (trailing != null) trailing!]);
}

class ExpenseStatus extends StatelessWidget {
  final String status;
  const ExpenseStatus(this.status, {super.key});
  @override
  Widget build(BuildContext context) {
    final rejected = status == 'rejetee';
    final validated = status == 'validee';
    final color = rejected ? Brand.amber : validated ? Brand.teal : Brand.ink;
    final bg = rejected ? Brand.amberSurface : validated ? Brand.mint : Brand.canvas;
    final icon = rejected ? Icons.error_outline_rounded : validated ? Icons.check_circle_outline_rounded : Icons.schedule_rounded;
    return Container(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6), decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(100)),
      child: Wrap(spacing: 5, runSpacing: 2, crossAxisAlignment: WrapCrossAlignment.center, children: [Icon(icon, size: 15, color: color), Text(statusLabel(status), style: TextStyle(color: color, fontSize: 12, fontWeight: FontWeight.w700))]));
  }
}

class ExpenseTile extends StatelessWidget {
  final Expense expense;
  final VoidCallback onTap;
  const ExpenseTile({super.key, required this.expense, required this.onTap});
  @override
  Widget build(BuildContext context) => Card(child: InkWell(
    borderRadius: BorderRadius.circular(20), onTap: onTap,
    child: Padding(padding: const EdgeInsets.all(15), child: Row(children: [
      Container(width: 44, height: 44, decoration: BoxDecoration(color: Brand.canvas, borderRadius: BorderRadius.circular(14)), child: const Icon(Icons.receipt_long_rounded, color: Brand.ink)),
      const SizedBox(width: 12),
      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(expense.supplier.isNotEmpty ? expense.supplier : expense.description.isNotEmpty ? expense.description : 'Facture #${expense.id}', maxLines: 2, overflow: TextOverflow.ellipsis, style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: 3), Text(shortDate(expense.invoiceDate), style: const TextStyle(fontSize: 12, color: Color(0xFF66777A))),
        const SizedBox(height: 8), Wrap(spacing: 8, runSpacing: 6, crossAxisAlignment: WrapCrossAlignment.center, children: [
          ExpenseStatus(expense.status),
          Text(money(expense.amount), style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Brand.ink)),
        ]),
      ])),
      const SizedBox(width: 4), const Icon(Icons.chevron_right_rounded, color: Brand.ink, size: 19),
    ]))));
}

class MessageState extends StatelessWidget {
  final IconData icon;
  final String title;
  final String message;
  final String? action;
  final VoidCallback? onAction;
  const MessageState({super.key, required this.icon, required this.title, required this.message, this.action, this.onAction});
  @override
  Widget build(BuildContext context) => Center(child: SingleChildScrollView(child: Padding(padding: const EdgeInsets.all(28), child: Column(mainAxisSize: MainAxisSize.min, children: [
    Container(padding: const EdgeInsets.all(20), decoration: const BoxDecoration(color: Brand.mint, shape: BoxShape.circle), child: Icon(icon, color: Brand.teal, size: 30)),
    const SizedBox(height: 18), Text(title, textAlign: TextAlign.center, style: Theme.of(context).textTheme.titleLarge),
    const SizedBox(height: 8), Text(message, textAlign: TextAlign.center, style: const TextStyle(color: Color(0xFF66777A), height: 1.4)),
    if (action != null && onAction != null) ...[const SizedBox(height: 18), TextButton(onPressed: onAction, child: Text(action!))],
  ]))));
}

void showError(BuildContext context, Object error) {
  if (!context.mounted) return;
  ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error.toString()), behavior: SnackBarBehavior.floating));
}
