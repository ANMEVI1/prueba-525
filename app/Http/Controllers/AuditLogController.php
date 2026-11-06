<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $actionFilter = $request->query('action');
        $search = trim((string) $request->query('buscar', ''));

        $logs = AuditLog::query()
            ->with('user')
            ->when($actionFilter, fn ($query) => $query->where('action', $actionFilter))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('audit.index', [
            'logs' => $logs,
            'actionFilter' => $actionFilter,
            'search' => $search,
            'actions' => AuditAction::cases(),
            'totalEvents' => AuditLog::query()->count(),
        ]);
    }
}
