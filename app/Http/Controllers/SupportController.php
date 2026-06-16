<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        $tickets = SupportTicket::where('user_id', $request->user()->id)
            ->withCount(['messages as unread_count' => function ($q) {
                $q->where('is_admin', true)->whereNull('read_at');
            }])
            ->latest('last_reply_at')
            ->latest('id')
            ->get();

        return view('client.support.index', compact('tickets'));
    }

    public function create()
    {
        return view('client.support.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'category' => ['required', 'in:general,deposit,withdrawal,kyc,technical'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'subject' => $data['subject'],
            'category' => $data['category'],
            'status' => 'open',
            'last_reply_at' => now(),
        ]);

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'is_admin' => false,
            'body' => $data['body'],
        ]);

        \App\Models\AppNotification::pushAdmins('message', 'New support ticket', $request->user()->name . ': ' . $ticket->subject, route('admin.messages.show', $ticket));

        return redirect()->route('support.show', $ticket)->with('status', 'Ticket created. Our team will reply shortly.');
    }

    public function show(Request $request, SupportTicket $ticket)
    {
        abort_unless($ticket->user_id === $request->user()->id, 403);

        // Mark admin replies as read for this client.
        $ticket->messages()->where('is_admin', true)->whereNull('read_at')->update(['read_at' => now()]);

        $ticket->load('messages.user');

        return view('client.support.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        abort_unless($ticket->user_id === $request->user()->id, 403);
        abort_if($ticket->status === 'closed', 403, 'This ticket is closed.');

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'is_admin' => false,
            'body' => $data['body'],
        ]);

        $ticket->update(['status' => 'open', 'last_reply_at' => now()]);

        \App\Models\AppNotification::pushAdmins('message', 'New reply on ticket', $request->user()->name . ': ' . $ticket->subject, route('admin.messages.show', $ticket));

        return back()->with('status', 'Reply sent.');
    }
}
