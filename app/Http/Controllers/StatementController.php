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
        $scope = $request->get('scope', 'fund');

        // Spot / combined statements use the multi-section view.
        if (in_array($scope, ['spot', 'all'])) {
            return $this->scopedStatement($request, $svc, $user, $start, $end, $label, $scope);
        }

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

    /** Spot / combined (all) statement. */
    private function scopedStatement(Request $request, StatementService $svc, $user, $start, $end, $label, $scope)
    {
        $payload = [
            'client' => $user, 'name' => $user->name, 'email' => $user->email, 'code' => $user->clientCode(),
            'label' => $label, 'start' => $start, 'end' => $end, 'generatedAt' => now(), 'scope' => $scope,
            'fund' => $scope === 'all' ? $svc->data($user, $start, $end, $label) : null,
            'spot' => in_array($scope, ['spot', 'all']) ? $svc->spotSection($user, $start, $end) : null,
        ];

        if ($request->get('action') === 'email') {
            $pdf = $svc->pdfFromView('pdf.account-statement', $payload);
            try {
                Mail::to($user->email)->send(new StatementMail($payload, $pdf?->output(), 'emails.statement-generic', 'Your GrowthCapital statement · ' . $label));
            } catch (\Throwable $e) {
                if ($request->wantsJson()) {
                    return response()->json(['ok' => false, 'message' => 'Could not send the statement right now.'], 500);
                }

                return back()->with('status', 'Could not send statement right now.');
            }
            if ($request->wantsJson()) {
                return response()->json(['ok' => true, 'message' => 'Statement emailed to ' . $user->email . '.']);
            }

            return back()->with('status', 'Statement sent to ' . $user->email . '.');
        }

        $pdf = $svc->pdfFromView('pdf.account-statement', $payload);
        if ($pdf) {
            return $pdf->download('GrowthCapital-Statement-' . $user->clientCode() . '.pdf');
        }

        return view('pdf.account-statement', $payload + ['print' => true]);
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
        $svc = app(\App\Services\SpotTradingService::class);

        // Original fiat amount + the conversion rate locked at the transaction (shown on its own line).
        $fiatSub = function ($row, $usd) {
            if (! $row->currency || $row->currency === 'USD' || $usd <= 0) {
                return null;
            }
            $sym = ['INR' => '₹', 'USD' => '$', 'EUR' => '€', 'GBP' => '£'][$row->currency] ?? ($row->currency . ' ');
            $rate = round((float) $row->amount / $usd, 2);
            return $sym . number_format((float) $row->amount, 2) . ' @ ' . number_format($rate, 2) . '/$';
        };
        \App\Models\Deposit::where('user_id', $user->id)->where('purpose', 'spot')->latest('id')->limit(40)->get()->each(function ($d) use ($spot, $svc, $fiatSub) {
            $usd = $d->usd_amount !== null ? (float) $d->usd_amount : $svc->toUsd((float) $d->amount, $d->currency ?: 'USD');
            $spot->push((object) ['when' => $d->created_at, 'kind' => 'deposit', 'label' => 'Spot deposit', 'sub' => $fiatSub($d, $usd),
                'amount' => $usd, 'cs' => '$', 'status' => ucfirst($d->status)]);
        });
        \App\Models\Withdrawal::where('user_id', $user->id)->where('purpose', 'spot')->latest('id')->limit(40)->get()->each(function ($w) use ($spot, $svc, $fiatSub) {
            $usd = $w->usd_amount !== null ? (float) $w->usd_amount : $svc->toUsd((float) $w->amount, $w->currency ?: 'USD');
            $spot->push((object) ['when' => $w->created_at, 'kind' => 'withdrawal', 'label' => 'Spot withdrawal', 'sub' => $fiatSub($w, $usd),
                'amount' => -1 * $usd, 'cs' => '$', 'status' => ucfirst($w->status)]);
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

        // Spot realized profit — reconstruct per instrument from the trade sequence (sell − avg cost).
        $user = $request->user();
        $trades = \App\Models\SpotTrade::with('instrument')
            ->where(fn ($q) => $q->where('buyer_id', $user->id)->orWhere('seller_id', $user->id))
            ->orderBy('id')->get();

        $pos = [];           // instrument_id => ['qty','avg']
        $spotProfits = collect();
        foreach ($trades as $t) {
            $iid = $t->instrument_id;
            $pos[$iid] ??= ['qty' => 0.0, 'avg' => 0.0];
            $qty = (float) $t->qty;
            $price = (float) $t->price;

            if ($t->buyer_id === $user->id) {
                $newQty = $pos[$iid]['qty'] + $qty;
                $pos[$iid]['avg'] = $newQty > 0 ? (($pos[$iid]['qty'] * $pos[$iid]['avg']) + ($qty * $price)) / $newQty : 0;
                $pos[$iid]['qty'] = $newQty;
            } elseif ($t->seller_id === $user->id) {
                $realized = round(($price - $pos[$iid]['avg']) * $qty, 2);
                $pos[$iid]['qty'] = max(0, $pos[$iid]['qty'] - $qty);
                $spotProfits->push((object) [
                    'when' => $t->created_at, 'symbol' => $t->instrument->symbol,
                    'qty' => $qty, 'realized' => $realized, 'cs' => $t->instrument->currencySymbol(),
                ]);
            }
        }
        $spotProfits = $spotProfits->sortByDesc('when')->values();

        return view('client.profit', compact('rows', 'totalProfit', 'period', 'spotProfits'));
    }
}
