@php
    $validationSteps = [
        ['label' => 'En attente', 'icon' => 'clock-3', 'count' => $stats['depenses_en_attente'], 'amount' => $stats['montant_en_attente']],
        ['label' => 'Approuvées', 'icon' => 'check', 'count' => $stats['depenses_acceptees'], 'amount' => $stats['montant_acceptees']],
        ['label' => 'Validées', 'icon' => 'check-check', 'count' => $stats['depenses_validees'], 'amount' => $stats['montant_validees']],
        ['label' => 'Rejetées', 'icon' => 'x', 'count' => $stats['depenses_rejetees'], 'amount' => $stats['montant_rejetees']],
    ];
    $statusLabels = [
        'en_attente' => 'En attente',
        'acceptee' => 'Approuvée',
        'validee' => 'Validée',
        'rejetee' => 'Rejetée',
    ];
@endphp
<div class="space-y-5 sm:space-y-6">
    <div class="md:hidden">
        <h1 class="text-[1.8rem] font-semibold tracking-tight text-[#232323]">Vue d’ensemble</h1>
        <p class="mt-1 text-sm text-[#676767]">Suivi de vos chantiers et dépenses</p>
    </div>

    <section class="rounded-xl border border-[#E7E5E1] bg-white" aria-label="Synthèse générale">
        <div class="grid divide-y divide-[#E7E5E1] lg:grid-cols-[minmax(0,1.65fr)_minmax(0,1fr)] lg:divide-x lg:divide-y-0">
            <div class="p-5 sm:p-7">
                <p class="flex items-center gap-2.5 text-sm text-[#555]"><x-icon name="coins" class="size-5 text-[#333]" />Montant des dépenses</p>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-[#171717] sm:text-4xl">{{ number_format($stats['total_montant_depenses'], 0, ',', ' ') }} <span class="text-2xl sm:text-3xl">FCFA</span></p>
                <p class="mt-2 text-sm text-[#6D6D6D]">Total cumulé des dépenses enregistrées</p>
            </div>
            <dl class="grid grid-cols-3 divide-x divide-[#E7E5E1] px-2 py-4 sm:px-4">
                <div class="min-w-0 px-2 sm:px-4"><dt class="flex flex-col gap-1 text-xs text-[#555] sm:flex-row sm:items-center sm:text-sm"><x-icon name="building-2" class="size-4 text-[#333]" />Chantiers</dt><dd class="mt-2 text-2xl font-semibold">{{ $stats['total_chantiers'] }}</dd><p class="hidden text-xs text-[#777] sm:block">chantiers au total</p></div>
                <div class="min-w-0 px-2 sm:px-4"><dt class="flex flex-col gap-1 text-xs text-[#555] sm:flex-row sm:items-center sm:text-sm"><x-icon name="receipt" class="size-4 text-[#333]" />Dépenses</dt><dd class="mt-2 text-2xl font-semibold">{{ $stats['total_depenses'] }}</dd><p class="hidden text-xs text-[#777] sm:block">enregistrées</p></div>
                <div class="min-w-0 px-2 sm:px-4"><dt class="flex flex-col gap-1 text-xs text-[#555] sm:flex-row sm:items-center sm:text-sm"><x-icon name="users-round" class="size-4 text-[#333]" />Utilisateurs</dt><dd class="mt-2 text-2xl font-semibold">{{ $stats['total_utilisateurs'] }}</dd><p class="hidden text-xs text-[#777] sm:block">au total</p></div>
            </dl>
        </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1.75fr)_minmax(20rem,1fr)]">
        <section class="min-w-0 rounded-xl border border-[#E7E5E1] bg-white p-4 sm:p-6" aria-labelledby="admin-recent-title">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                <div><h2 id="admin-recent-title" class="flex items-center gap-2.5 text-lg font-semibold"><x-icon name="receipt" class="size-5" />Dépenses récentes</h2><p class="mt-0.5 text-sm text-[#777]">Dernières dépenses enregistrées sur vos chantiers</p></div>
                <div class="flex items-center gap-3"><a href="{{ route('depenses.export.csv') }}" class="inline-flex min-h-9 items-center gap-2 rounded-lg border border-[#DDDAD5] px-3 text-sm hover:bg-[#F7F6F3]"><x-icon name="download" class="size-4" />Exporter</a><a href="{{ route('depenses.index') }}" class="text-sm font-medium text-[#C31F28] hover:underline">Voir tout →</a></div>
            </div>
            <div class="hidden min-h-66 overflow-x-auto rounded-lg border border-[#E7E5E1] md:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#FAF9F7] text-xs font-medium text-[#555]"><tr><th scope="col" class="px-4 py-3">Description</th><th scope="col" class="px-4 py-3">Chantier</th><th scope="col" class="px-4 py-3">Statut</th><th scope="col" class="px-4 py-3 text-right">Montant</th><th scope="col" class="px-4 py-3 text-right">Date</th></tr></thead>
                    <tbody class="divide-y divide-[#E7E5E1]">
                        @forelse($depensesRecentes as $depense)
                            <tr class="hover:bg-[#FAF9F7]"><td class="px-4 py-4"><a class="font-medium hover:text-[#C31F28]" href="{{ route('depenses.show', $depense) }}">{{ $depense->libelle }}</a></td><td class="max-w-52 px-4 py-4 text-[#555]">{{ $depense->chantier?->nom ?? 'Chantier archivé' }}<span class="block text-xs text-[#777]">{{ $depense->utilisateur?->name ?? 'Utilisateur supprimé' }}</span></td><td class="px-4 py-4"><span class="inline-flex rounded-full border border-[#D6D3CE] px-2.5 py-1 text-xs">{{ $statusLabels[$depense->statut_validation] ?? $depense->statut_validation }}</span></td><td class="whitespace-nowrap px-4 py-4 text-right font-semibold">{{ number_format($depense->prix_total, 0, ',', ' ') }} FCFA</td><td class="whitespace-nowrap px-4 py-4 text-right text-xs text-[#777]">{{ $depense->created_at?->format('d/m/Y') }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-[#777]">Aucune dépense récente.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="space-y-2 md:hidden">
                @forelse($depensesRecentes as $depense)
                    <a href="{{ route('depenses.show', $depense) }}" class="block rounded-lg border border-[#E7E5E1] p-3 hover:bg-[#FAF9F7]"><span class="flex items-start justify-between gap-3"><strong class="text-sm">{{ $depense->libelle }}</strong><span class="shrink-0 text-sm font-semibold">{{ number_format($depense->prix_total, 0, ',', ' ') }} F</span></span><span class="mt-1 block text-xs text-[#777]">{{ $depense->chantier?->nom ?? 'Chantier archivé' }}</span><span class="mt-2 inline-flex rounded-full border border-[#D6D3CE] px-2 py-0.5 text-xs">{{ $statusLabels[$depense->statut_validation] ?? $depense->statut_validation }}</span></a>
                @empty
                    <p class="rounded-lg border border-[#E7E5E1] p-5 text-center text-sm text-[#777]">Aucune dépense récente.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-xl border border-[#E7E5E1] bg-white p-4 sm:p-6" aria-labelledby="admin-budgets-title">
            <div class="mb-5 flex items-start justify-between gap-3"><div><h2 id="admin-budgets-title" class="flex items-center gap-2.5 text-lg font-semibold"><x-icon name="chart-no-axes-column" class="size-5" />Suivi des budgets</h2><p class="mt-0.5 text-sm text-[#777]">Avancement de vos chantiers</p></div><a href="{{ route('chantiers.index') }}" class="whitespace-nowrap text-sm font-medium text-[#C31F28] hover:underline">Voir tout →</a></div>
            <div class="divide-y divide-[#E7E5E1]">
                @forelse($chantiersActifs as $chantier)
                    @php $percentage = max(0, $chantier->pourcentage_consomme); @endphp
                    <div class="py-4 first:pt-0 last:pb-0"><div class="flex items-start justify-between gap-3"><div class="min-w-0"><a href="{{ route('chantiers.show', $chantier) }}" class="text-sm font-semibold hover:text-[#C31F28]">{{ $chantier->nom }}</a><p class="mt-0.5 text-xs text-[#777]">{{ $chantier->lieu }}</p></div><span class="shrink-0 text-sm font-semibold">{{ number_format($percentage, 1, ',', ' ') }} %</span></div><div class="mt-3 h-2 overflow-hidden rounded-full bg-[#ECEAE7]" role="progressbar" aria-label="Budget utilisé pour {{ $chantier->nom }}" aria-valuenow="{{ min(100, $percentage) }}" aria-valuemin="0" aria-valuemax="100"><div class="h-full rounded-full bg-[#D7232A]" style="width: {{ min(100, $percentage) }}%"></div></div><div class="mt-2 flex flex-wrap justify-between gap-x-3 gap-y-1 text-xs text-[#777]"><span>Consommé : <strong class="font-medium text-[#232323]">{{ number_format($chantier->budget_consomme, 0, ',', ' ') }} F</strong></span><span>Restant : <strong class="font-medium text-[#232323]">{{ number_format($chantier->budget_restant, 0, ',', ' ') }} F</strong></span></div></div>
                @empty
                    <p class="py-6 text-sm text-[#777]">Aucun chantier actif.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="rounded-xl border border-[#E7E5E1] bg-white p-4 sm:p-6" aria-labelledby="admin-validation-title">
        <h2 id="admin-validation-title" class="flex items-center gap-2.5 text-lg font-semibold"><x-icon name="clock-3" class="size-5" />Étapes de validation</h2>
        <p class="mt-0.5 text-sm text-[#777]">Statut des dépenses dans le processus de validation</p>
        <dl class="mt-5 grid grid-cols-2 gap-y-5 sm:grid-cols-4 sm:gap-y-0 sm:divide-x sm:divide-[#E7E5E1]">
            @foreach($validationSteps as $step)
                <div class="min-w-0 sm:px-5 sm:first:pl-0 sm:last:pr-0"><dt class="flex items-center gap-2 text-sm text-[#555]"><x-icon :name="$step['icon']" class="size-4" />{{ $step['label'] }}</dt><dd class="mt-1 text-2xl font-semibold">{{ $step['count'] }}</dd><p class="mt-0.5 text-xs text-[#777]">{{ number_format($step['amount'], 0, ',', ' ') }} FCFA</p></div>
            @endforeach
        </dl>
    </section>
</div>
