@extends('layouts.admin')

@section('title', 'Journal d’audit')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Journal d’audit</h1>
        <p class="mt-1 text-sm text-gray-600">Traçabilité des accès et opérations sensibles.</p>
    </div>

    <form method="GET" class="grid gap-4 rounded-xl bg-white p-4 shadow-sm md:grid-cols-4">
        <select name="event" class="rounded-lg border-gray-300">
            <option value="">Tous les événements</option>
            @foreach ($events as $event)
                <option value="{{ $event }}" @selected(request('event') === $event)>{{ $event }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border-gray-300">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border-gray-300">
        <button class="rounded-lg bg-[#E31E24] px-4 py-2 font-semibold text-white">Filtrer</button>
    </form>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Événement</th><th class="px-4 py-3">Acteur</th><th class="px-4 py-3">Détail</th><th class="px-4 py-3">IP</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="px-4 py-3 font-semibold">{{ $log->event }}</td>
                        <td class="px-4 py-3">{{ $log->actor?->name ?? 'Système / inconnu' }}</td>
                        <td class="px-4 py-3">{{ $log->description }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Aucun événement.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $logs->links() }}
</div>
@endsection
