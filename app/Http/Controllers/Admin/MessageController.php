<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Services\Notifier;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('status', 'all');

        $tickets = SupportTicket::with('user')
            ->withCount(['messages as unread_count' => function ($q) {
                $q->where('is_admin', false)->whereNull('read_at');
            }])
            ->when(in_array($filter, ['open', 'answered', 'closed']), fn ($q) => $q->where('status', $filter))
            ->latest('last_reply_at')
            ->latest('id')
            ->get();

        return view('admin.messages.index', compact('tickets', 'filter'));
    }

    public function show(SupportTicket $ticket)
    {
        // Mark client messages as read.
        $ticket->messages()->where('is_admin', false)->whereNull('read_at')->update(['read_at' => now()]);

        $ticket->load('messages.user', 'user');

        return view('admin.messages.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'is_admin' => true,
            'body' => $data['body'],
        ]);

        $ticket->update(['status' => 'answered', 'last_reply_at' => now()]);

        Notifier::send(
            $ticket->user,
            'Support replied to your ticket',
            'New reply on your support ticket',
            [
                'Our team has replied to your ticket: "' . $ticket->subject . '".',
                'Log in to your dashboard to read the reply and respond.',
            ],
            route('support.show', $ticket),
            'View ticket',
        );

        return back()->with('status', 'Reply sent to client.');
    }

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate(['status' => ['required', 'in:open,answered,closed']]);
        $ticket->update(['status' => $data['status']]);

        return back()->with('status', 'Ticket marked as ' . $data['status'] . '.');
    }
}
