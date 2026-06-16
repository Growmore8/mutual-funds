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
        $html = view('pdf.statement', $data)->render();

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4');
        }

        return null;
    }

    public function filename(array $data): string
    {
        return 'GrowthCapital-Statement-' . $data['code'] . '-' . $data['start']->format('Ymd') . '-' . $data['end']->format('Ymd') . '.pdf';
    }
}
