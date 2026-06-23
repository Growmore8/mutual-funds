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
                if ($request->wantsJson()) {
                    return response()->json(['ok' => false, 'message' => 'Could not send the statement right now. Please try again later.'], 500);
                }

                return back()->with('status', 'Could not send statement right now. Please try again later.');
            }

            if ($request->wantsJson()) {
                return response()->json(['ok' => true, 'message' => 'Statement emailed to ' . $user->email . '.']);
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

        // Spot Trading activity = trades + spot deposits/withdrawals (separate ledger).
        $user = $request->user();
        $spot = collect();
        \App\Models\SpotTrade::with('instrument')
            ->where(fn ($q) => $q->where('buyer_id', $user->id)->orWhere('seller_id', $user->id))
            ->latest('id')->limit(60)->get()->each(function ($t) use ($spot, $user) {
                $isBuy = $t->buyer_id === $user->id;
                $spot->push((object) ['when' => $t->created_at, 'kind' => $isBuy ? 'buy' : 'sell',
                    'label' => ($isBuy ? 'Buy ' : 'Sell ') . $t->instrument->symbol . ' ×' . rtrim(rtrim((string) $t->qty, '0'), '.'),
                    'amount' => ($isBuy ? -1 : 1) * (float) $t->qty * (float) $t->price, 'cs' => $t->instrument->currencySymbol(), 'status' => 'Filled']);
            });
        \App\Models\Deposit::where('user_id', $user->id)->where('purpose', 'spot')->latest('id')->limit(40)->get()->each(function ($d) use ($spot) {
            $spot->push((object) ['when' => $d->created_at, 'kind' => 'deposit', 'label' => 'Spot deposit (' . $d->currency . ')',
                'amount' => (float) $d->amount, 'cs' => $d->currency === 'INR' ? '₹' : '$', 'status' => ucfirst($d->status)]);
        });
        \App\Models\Withdrawal::where('user_id', $user->id)->where('purpose', 'spot')->latest('id')->limit(40)->get()->each(function ($w) use ($spot) {
            $spot->push((object) ['when' => $w->created_at, 'kind' => 'withdrawal', 'label' => 'Spot withdrawal (' . $w->currency . ')',
                'amount' => -1 * (float) $w->amount, 'cs' => $w->currency === 'INR' ? '₹' : '$', 'status' => ucfirst($w->status)]);
        });
        $spotActivity = $spot->sortByDesc('when')->values();

        return view('client.transactions', compact('transactions', 'type', 'spotActivity'));
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
