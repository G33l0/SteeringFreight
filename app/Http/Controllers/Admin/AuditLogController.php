<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('audit.view');

        return view('admin.audit.index', [
            'logs' => AuditLog::with('user')
                ->search($request->string('q')->trim()->value())
                ->when($request->integer('user'), fn ($query, $user) => $query->where('user_id', $user))
                ->when($request->date('from'), fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
                ->when($request->date('to'), fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
                ->latest('id')
                ->paginate((int) config('portlane.per_page.admin'))
                ->withQueryString(),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'filters' => [
                'q' => $request->string('q')->value(),
                'user' => $request->integer('user') ?: null,
                'from' => $request->date('from')?->toDateString(),
                'to' => $request->date('to')?->toDateString(),
            ],
        ]);
    }
}
