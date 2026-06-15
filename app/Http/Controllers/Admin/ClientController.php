<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $clients = User::where('role', 'client')
            ->when($request->q, fn ($q) => $q->where(fn ($w) =>
                $w->where('name', 'like', "%{$request->q}%")->orWhere('email', 'like', "%{$request->q}%")))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.clients.index', compact('clients'));
    }

    public function show(User $client)
    {
        abort_unless($client->role === 'client', 404);
        $client->load(['accountType', 'deposits.paymentMethod', 'transactions' => fn ($q) => $q->latest()->limit(50), 'kycDocuments']);

        return view('admin.clients.show', compact('client'));
    }

    public function updateStatus(Request $request, User $client)
    {
        $data = $request->validate(['status' => ['required', 'in:pending,active,suspended']]);
        $client->update($data);

        return back()->with('status', "Client status updated to {$data['status']}.");
    }
}
