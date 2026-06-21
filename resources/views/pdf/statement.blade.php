@php $m = fn ($n) => '$' . number_format((float) $n, 2); @endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, Arial, sans-serif; }
    body { color:#1f2937; font-size:12px; margin:0; }
    .wrap { padding:28px 32px; }
    .head { border-bottom:3px solid #10b981; padding-bottom:14px; margin-bottom:18px; }
    .brand { font-size:20px; font-weight:bold; color:#0a1730; }
    .brand span { color:#10b981; }
    .sub { color:#6b7280; font-size:11px; margin-top:2px; }
    .title { font-size:14px; font-weight:bold; margin:0; }
    .muted { color:#6b7280; }
    table { width:100%; border-collapse:collapse; }
    .info td { padding:4px 0; vertical-align:top; }
    .info .k { color:#6b7280; width:130px; }
    .cards { width:100%; margin:16px 0; }
    .cards td { width:25%; padding:10px; border:1px solid #e5e7eb; border-radius:6px; }
    .card-l { color:#6b7280; font-size:10px; text-transform:uppercase; letter-spacing:.04em; }
    .card-v { font-size:15px; font-weight:bold; margin-top:3px; }
    .pos { color:#059669; } .neg { color:#dc2626; }
    .tx { margin-top:8px; }
    .tx th { background:#f3f4f6; color:#374151; text-align:left; padding:7px 8px; font-size:11px; border-bottom:1px solid #e5e7eb; }
    .tx td { padding:7px 8px; border-bottom:1px solid #f1f5f9; }
    .right { text-align:right; }
    .foot { margin-top:22px; border-top:1px solid #e5e7eb; padding-top:10px; color:#9ca3af; font-size:10px; }
    .sec { font-weight:bold; margin:18px 0 6px; color:#0a1730; }
    .no-print{font-family:Arial,sans-serif}
    @media print{.no-print{display:none !important}}
</style>
</head>
<body>
@isset($print)
    <div class="no-print" style="position:sticky;top:0;z-index:10;background:#0a1730;color:#fff;padding:12px 16px;display:flex;gap:10px;align-items:center;justify-content:center;flex-wrap:wrap">
        <span style="font-weight:bold;margin-right:6px">GrowthCapital Statement</span>
        <button onclick="window.print()" style="cursor:pointer;border:0;background:#10b981;color:#fff;font-weight:bold;padding:9px 16px;border-radius:8px">⬇ Download / Save PDF</button>
        <button onclick="window.close()" style="cursor:pointer;border:1px solid rgba(255,255,255,.3);background:transparent;color:#fff;padding:9px 16px;border-radius:8px">Close</button>
    </div>
@endisset
<div class="wrap">
    <div class="head">
        <table>
            <tr>
                <td>
                    <div class="brand">Growth<span>Capital</span> Mutual Fund</div>
                    <div class="sub">Managed pool account statement</div>
                </td>
                <td class="right">
                    <p class="title">Account Statement</p>
                    <div class="sub">{{ $label }}</div>
                    <div class="sub">{{ $start->format('d M Y') }} – {{ $end->format('d M Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="info">
        <tr><td class="k">Client name</td><td><strong>{{ $name }}</strong></td>
            <td class="k">Client ID</td><td><strong>{{ $code }}</strong></td></tr>
        <tr><td class="k">Email</td><td>{{ $email }}</td>
            <td class="k">Account type</td><td>{{ $accountType }}</td></tr>
        <tr><td class="k">Profit share</td><td>{{ rtrim(rtrim(number_format($sharePct, 2), '0'), '.') }}%</td>
            <td class="k">Generated</td><td>{{ $generatedAt->format('d M Y h:i A') }}</td></tr>
    </table>

    <div class="sec">Account summary</div>
    <table class="cards">
        <tr>
            <td><div class="card-l">Total Deposit</div><div class="card-v">{{ $m($totalDeposit) }}</div></td>
            <td><div class="card-l">Total Withdrawal</div><div class="card-v">{{ $m($totalWithdrawn) }}</div></td>
            <td><div class="card-l">PnL (running)</div><div class="card-v {{ $pnl < 0 ? 'neg' : 'pos' }}">{{ ($pnl < 0 ? '-' : '+') . $m(abs($pnl)) }}</div></td>
            <td><div class="card-l">Floating PnL</div><div class="card-v {{ $floatingPnl < 0 ? 'neg' : 'pos' }}">{{ ($floatingPnl < 0 ? '-' : '+') . $m(abs($floatingPnl)) }}</div></td>
        </tr>
    </table>

    <div class="sec">This period ({{ $label }})</div>
    <table class="cards">
        <tr>
            <td><div class="card-l">Deposits</div><div class="card-v">{{ $m($periodDeposit) }}</div></td>
            <td><div class="card-l">Withdrawals</div><div class="card-v">{{ $m($periodWithdrawal) }}</div></td>
            <td><div class="card-l">Profit credited</div><div class="card-v {{ $periodProfit < 0 ? 'neg' : 'pos' }}">{{ ($periodProfit < 0 ? '-' : '+') . $m(abs($periodProfit)) }}</div></td>
            <td><div class="card-l">Profit share</div><div class="card-v">{{ rtrim(rtrim(number_format($sharePct, 2), '0'), '.') }}%</div></td>
        </tr>
    </table>

    <div class="sec">Transactions</div>
    <table class="tx">
        <thead>
            <tr><th>Date</th><th>Type</th><th>Description</th><th class="right">Amount</th><th class="right">Balance</th></tr>
        </thead>
        <tbody>
            @forelse ($transactions as $t)
                <tr>
                    <td>{{ $t->created_at->format('d M Y h:i A') }}</td>
                    <td>{{ ucfirst($t->type) }}</td>
                    <td>{{ $t->description ?? '—' }}</td>
                    <td class="right {{ $t->amount < 0 ? 'neg' : 'pos' }}">{{ ($t->amount < 0 ? '' : '+') . $m($t->amount) }}</td>
                    <td class="right">{{ $m($t->balance_after) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:16px;">No transactions in this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="foot">
        GrowthCapital Ltd. — GrowthCapital Mutual Fund · © {{ date('Y') }} All rights reserved.<br>
        This statement is generated automatically. Figures are indicative; floating PnL reflects open positions and changes with the market.
    </div>
</div>

</body>
</html>
