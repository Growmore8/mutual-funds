<?php

namespace App\Http\Controllers;

use App\Mail\StatementMail;
use App\Models\PnlAllocation;
use App\Models\Transaction;
use App\Services\StatementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class StatementController extends Controller
{
    /** Download or email the client's own PDF statement for a chosen period. */
    public function statement(Request $request, StatementService $svc)
    {
        $user = $request->user();
        [$start, $end, $label] = $svc->period($request->get('period', 'month'), $request->get('from'), $request->get('to'));
        $data = $svc->data($user, $start, $end, $label);

        if ($request->get('action') === 'email') {
            $pdf = $svc->pdf($data);
            try {
                Mail::to($user->email)->send(new StatementMail($data, $pdf?->output()));
            } catch (\Throwable $e) {
                return back()->with('status', 'Could not send statement right now. Please try again later.');
            }

            return back()->with('status', 'Statement sent to ' . $user->email . '.');
        }

        $pdf = $svc->pdf($data);
        if ($pdf) {
            return $pdf->download($svc->filename($data));
        }

        return view('pdf.statement', $data + ['print' => true]);
    }

    /** Deposit / withdrawal / profit transaction history. */
    public function transactions(Request $request)
    {
        $type = $request->get('type');
        $aid = $request->user()->currentAccount()?->id;

        $transactions = Transaction::where('fund_account_id', $aid)
            ->when(in_array($type, ['deposit', 'withdrawal', 'profit', 'fee', 'adjustment']),
                fn ($q) => $q->where('type', $type))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('client.transactions', compact('transactions', 'type'));
    }

    /** Full profit history — every profit/loss distribution event, with period filter. */
    public function profit(Request $request, StatementService $svc)
    {
        $period = $request->get('period', 'all');
        $account = $request->user()->currentAccount();
        $aid = $account?->id;

        $rows = Transaction::where('fund_account_id', $aid)
            ->where('type', 'profit')
            ->when(in_array($period, ['today', 'week', 'month', 'year', 'custom']), function ($q) use ($svc, $period, $request) {
                [$start, $end] = $svc->period($period, $request->get('from'), $request->get('to'));
                $q->whereBetween('created_at', [$start, $end]);
            })
            ->latest('id')
            ->paginate(60)
            ->withQueryString();

        $totalProfit = (float) Transaction::where('fund_account_id', $aid)->where('type', 'profit')->sum('amount');

        return view('client.profit', compact('rows', 'totalProfit', 'period'));
    }
}
