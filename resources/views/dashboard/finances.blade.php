<section class="mb-6 space-y-5" aria-labelledby="situation-financiere-title">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 id="situation-financiere-title" class="text-xl font-semibold tracking-tight text-[#232323]">Situation financière des chantiers</h2>
            <p class="mt-1 text-sm text-[#676767]">Solde disponible = budget alloué − dépenses validées. Les factures en attente restent affichées séparément.</p>
        </div>
        <a href="{{ route('chantiers.index') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-[#E7E5E1] bg-white px-4 text-sm font-semibold hover:bg-[#FCFBF9]">Tous les chantiers <x-icon name="chevron-right" class="size-4" /></a>
    </div>
    @php
        $financeCards = [
            ['label' => 'Budget alloué', 'value' => $portfolio['budget_alloue'], 'icon' => 'wallet'],
            ['label' => 'Factures en attente', 'value' => $portfolio['factures_en_attente'], 'icon' => 'clock-3'],
            ['label' => 'Dépenses validées', 'value' => $portfolio['depenses_validees'], 'icon' => 'check-check'],
            ['label' => 'Réellement payé', 'value' => $portfolio['montant_paye'], 'icon' => 'banknote'],
            ['label' => 'Solde disponible', 'value' => $portfolio['solde_disponible'], 'icon' => 'coins'],
        ];
    @endphp
    <dl class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @foreach($financeCards as $card)
            <div class="min-w-0 rounded-xl border border-[#E7E5E1] bg-white p-4 sm:p-5">
                <dt class="flex items-center gap-2 text-sm font-medium text-[#555]"><x-icon :name="$card['icon']" class="size-5 text-[#C31F28]" />{{ $card['label'] }}</dt>
                <dd class="mt-3 text-xl font-semibold tracking-tight {{ $card['label'] === 'Solde disponible' && (float) $card['value'] < 0 ? 'text-[#C31F28]' : 'text-[#232323]' }}">{{ number_format((float) $card['value'], 0, ',', ' ') }} <span class="text-sm font-medium">F</span></dd>
            </div>
        @endforeach
    </dl>
    <p class="text-sm text-[#676767]">Sur les dépenses validées, <strong class="text-[#232323]">{{ number_format((float) $portfolio['reste_a_payer'], 0, ',', ' ') }} F</strong> restent à payer.</p>
    @if($portfolio['factures_historiques_a_rapprocher'] > 0)
        <p role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">{{ $portfolio['factures_historiques_a_rapprocher'] }} ancienne(s) facture(s) marquée(s) payée(s), soit {{ number_format((float) $portfolio['historique_a_rapprocher'], 0, ',', ' ') }} F, attendent un rapprochement comptable. Ce montant est exclu du total réellement payé.</p>
    @endif
    @if($chantiersActifs->isNotEmpty())
        <div class="overflow-x-auto rounded-xl border border-[#E7E5E1] bg-white">
            <table class="w-full min-w-[780px] text-left text-sm">
                <caption class="sr-only">Situation financière des chantiers actifs</caption>
                <thead class="bg-[#FAF9F7] text-[#555]"><tr><th scope="col" class="px-4 py-3">Chantier</th><th scope="col" class="px-4 py-3 text-right">Alloué</th><th scope="col" class="px-4 py-3 text-right">En attente</th><th scope="col" class="px-4 py-3 text-right">Validé</th><th scope="col" class="px-4 py-3 text-right">Payé</th><th scope="col" class="px-4 py-3 text-right">Solde</th></tr></thead>
                <tbody class="divide-y divide-[#E7E5E1]">
                    @foreach($chantiersActifs as $chantier)
                        @php $finance = $synthesesChantiers[$chantier->id]; @endphp
                        <tr>
                            <td class="px-4 py-3 font-semibold"><a href="{{ route('chantiers.show', $chantier) }}" class="hover:text-[#C31F28] hover:underline">{{ $chantier->nom }}</a></td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">{{ number_format((float) $finance['budget_alloue'], 0, ',', ' ') }} F</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">{{ number_format((float) $finance['factures_en_attente'], 0, ',', ' ') }} F</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">{{ number_format((float) $finance['depenses_validees'], 0, ',', ' ') }} F</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">{{ number_format((float) $finance['montant_paye'], 0, ',', ' ') }} F</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold {{ (float) $finance['solde_disponible'] < 0 ? 'text-[#C31F28]' : '' }}">{{ number_format((float) $finance['solde_disponible'], 0, ',', ' ') }} F</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
