<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;

class StatementService
{
    /** Resolve an on-demand period key to [start, end, label]. */
    public function period(string $period, ?string $from = null, ?string $to = null): array
    {
        $now = now();

        return match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'Today, ' . $now->format('d M Y')],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek(), 'Week of ' . $now->copy()->startOfWeek()->format('d M Y')],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear(), 'Year ' . $now->year],
            'custom' => [
                $from ? Carbon::parse($from)->startOfDay() : $now->copy()->startOfMonth(),
                $to ? Carbon::parse($to)->endOfDay() : $now->copy()->endOfDay(),
                'Custom range',
            ],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), $now->format('F Y')],
        };
    }

    /** Previous full period for scheduled auto-send (weekly|monthly). */
    public function previousPeriod(string $period): array
    {
        $now = now();

        if ($period === 'weekly') {
            $s = $now->copy()->subWeek()->startOfWeek();

            return [$s, $s->copy()->endOfWeek(), 'Week of ' . $s->format('d M Y')];
        }

        $s = $now->copy()->subMonth()->startOfMonth();

        return [$s, $s->copy()->endOfMonth(), $s->format('F Y')];
    }

    /** Gather every field the statement shows for one client over a period. */
    public function data(User $client, Carbon $start, Carbon $end, string $label): array
    {
        $client->loadMissing('accountType', 'poolAccount');
        $at = $client->accountType;
        $pool = $client->poolAccount;

        $totalDeposit = $client->totalDeposited();
        $poolAmount = (float) ($at->pool_amount ?? ($pool->capacity ?? 0));
        $weight = $poolAmount > 0 ? min(1, $totalDeposit / $poolAmount) : 0;
        $sharePct = $poolAmount > 0 ? round($totalDeposit / $poolAmount * 100, 2) : 0;
        $floatingPnl = $pool ? round($weight * (float) $pool->floating_pnl, 2) : 0.0;

        $periodDeposit = (float) $client->deposits()->where('status', 'approved')
            ->whereBetween('value_date', [$start->toDateString(), $end->toDateString()])->sum('amount');
        $periodWithdrawal = (float) $client->withdrawals()->where('status', 'approved')
            ->whereBetween('processed_at', [$start, $end])->sum('amount');
        $periodProfit = (float) $client->transactions()->where('type', 'profit')
            ->whereBetween('created_at', [$start, $end])->sum('amount');

        $transactions = $client->transactions()
            ->whereBetween('created_at', [$start, $end])->orderBy('id')->get();

        return [
            'client' => $client,
            'label' => $label,
            'start' => $start,
            'end' => $end,
            'name' => $client->name,
            'email' => $client->email,
            'code' => $client->clientCode(),
            'accountType' => $at->name ?? '—',
            'sharePct' => $sharePct,
            'totalDeposit' => $totalDeposit,
            'totalWithdrawn' => (float) $client->withdrawals()->where('status', 'approved')->sum('amount'),
            'periodDeposit' => $periodDeposit,
            'periodWithdrawal' => $periodWithdrawal,
            'periodProfit' => $periodProfit,
            'pnl' => $client->runningPnl(),
            'floatingPnl' => $floatingPnl,
            'transactions' => $transactions,
            'generatedAt' => now(),
        ];
    }

    /** Render the statement PDF, or null if no PDF engine is installed. */
    public function pdf(array $data)
    {
        return $this->pdfFromView('pdf.statement', $data);
    }

    /** Render any statement view to a PDF (or null if no engine). */
    public function pdfFromView(string $view, array $data)
    {
        $html = view($view, $data)->render();

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4');
        }

        return null;
    }

    /** Spot Trading section for a currency over a period (trades + deposits/withdrawals + realized). */
    public function spotSection(User $client, Carbon $start, Carbon $end, string $currency): array
    {
        $cs = $currency === 'INR' ? '₹' : '$';
        $instIds = \App\Models\SpotInstrument::where('currency', $currency)->pluck('id');

        $allTrades = \App\Models\SpotTrade::with('instrument')->whereIn('instrument_id', $instIds)
            ->where(fn ($q) => $q->where('buyer_id', $client->id)->orWhere('seller_id', $client->id))
            ->orderBy('id')->get();

        // Walk all trades to compute realized within the period.
        $pos = [];
        $realized = 0.0;
        $periodTrades = collect();
        foreach ($allTrades as $t) {
            $iid = $t->instrument_id;
            $pos[$iid] ??= ['qty' => 0.0, 'avg' => 0.0];
            $qty = (float) $t->qty;
            $price = (float) $t->price;
            $inPeriod = $t->created_at->betweenIncluded($start, $end);
            if ($t->buyer_id === $client->id) {
                $nq = $pos[$iid]['qty'] + $qty;
                $pos[$iid]['avg'] = $nq > 0 ? (($pos[$iid]['qty'] * $pos[$iid]['avg']) + ($qty * $price)) / $nq : 0;
                $pos[$iid]['qty'] = $nq;
            } else {
                if ($inPeriod) {
                    $realized += ($price - $pos[$iid]['avg']) * $qty;
                }
                $pos[$iid]['qty'] = max(0, $pos[$iid]['qty'] - $qty);
            }
            if ($inPeriod) {
                $periodTrades->push($t);
            }
        }

        $deposits = (float) \App\Models\Deposit::where('user_id', $client->id)->where('purpose', 'spot')->where('currency', $currency)
            ->where('status', 'approved')->whereBetween('created_at', [$start, $end])->sum('amount');
        $withdrawals = (float) \App\Models\Withdrawal::where('user_id', $client->id)->where('purpose', 'spot')->where('currency', $currency)
            ->where('status', 'approved')->whereBetween('created_at', [$start, $end])->sum('amount');
        $balance = (float) \App\Models\SpotAccount::where('user_id', $client->id)->where('currency', $currency)->value('balance');

        return [
            'currency' => $currency, 'cs' => $cs,
            'deposits' => $deposits, 'withdrawals' => $withdrawals,
            'realized' => round($realized, 2), 'balance' => $balance,
            'trades' => $periodTrades, 'clientId' => $client->id,
        ];
    }

    public function filename(array $data): string
    {
        return 'GrowthCapital-Statement-' . $data['code'] . '-' . $data['start']->format('Ymd') . '-' . $data['end']->format('Ymd') . '.pdf';
    }
}
