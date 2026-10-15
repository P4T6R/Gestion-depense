<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'event' => ['nullable', 'string', 'max:80'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $logs = AuditLog::query()
            ->with(['actor:id,name,email', 'targetUser:id,name,email'])
            ->when($validated['event'] ?? null, fn ($query, $event) => $query->where('event', $event))
            ->when($validated['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($validated['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        $events = AuditLog::query()->distinct()->orderBy('event')->pluck('event');

        return view('audit.index', compact('logs', 'events'));
    }
}
