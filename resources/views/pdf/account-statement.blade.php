<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    *{font-family:DejaVu Sans, Arial, sans-serif}
    body{color:#1f2937;font-size:12px;margin:0}
    .wrap{padding:28px 32px}
    .head{border-bottom:3px solid #10b981;padding-bottom:14px;margin-bottom:18px}
    .brand{font-size:20px;font-weight:bold;color:#0a1730}
    .brand span{color:#10b981}
    .sub{color:#6b7280;font-size:11px;margin-top:2px}
    .sec{font-weight:bold;margin:18px 0 8px;color:#0a1730;border-left:3px solid #10b981;padding-left:8px}
    table{width:100%;border-collapse:collapse}
    .info td{padding:3px 0}.info .k{color:#6b7280;width:130px}
    .cards td{border:1px solid #e5e7eb;padding:10px;border-radius:6px}
    .card-l{color:#6b7280;font-size:10px}.card-v{font-size:15px;font-weight:bold}
    .tbl th{background:#f3f4f6;text-align:left;padding:6px;color:#6b7280;font-size:10px}
    .tbl td{padding:6px;border-bottom:1px solid #eee}
    .pos{color:#059669}.neg{color:#dc2626}
    .no-print{font-family:Arial}@media print{.no-print{display:none !important}}
</style>
</head>
<body>
@isset($print)
    <div class="no-print" style="position:sticky;top:0;z-index:10;background:#0a1730;color:#fff;padding:12px 16px;text-align:center">
        <span style="font-weight:bold;margin-right:8px">GrowthCapital Statement</span>
        <button onclick="window.print()" style="cursor:pointer;border:0;background:#10b981;color:#fff;font-weight:bold;padding:9px 16px;border-radius:8px">⬇ Download / Save PDF</button>
        <button onclick="window.close()" style="cursor:pointer;border:1px solid rgba(255,255,255,.3);background:transparent;color:#fff;padding:9px 16px;border-radius:8px;margin-left:6px">Close</button>
    </div>
@endisset

<div class="wrap">
    <div class="head">
        <div class="brand">Growth<span>Capital</span></div>
        <div class="sub">Account statement · {{ $label }}</div>
    </div>

    <table class="info">
        <tr><td class="k">Name</td><td>{{ $name }}</td><td class="k">Client ID</td><td>{{ $code }}</td></tr>
        <tr><td class="k">Email</td><td>{{ $email }}</td><td class="k">Generated</td><td>{{ $generatedAt->format('d M Y H:i') }}</td></tr>
        <tr><td class="k">Period</td><td colspan="3">{{ $start->format('d M Y') }} – {{ $end->format('d M Y') }}</td></tr>
    </table>

    {{-- Mutual Fund (only for 'all') --}}
    @if (!empty($fund))
        <div class="sec">Mutual Fund (USD)</div>
        <table class="cards"><tr>
            <td><div class="card-l">Capital</div><div class="card-v">${{ number_format($fund['totalDeposit'],2) }}</div></td>
            <td><div class="card-l">Deposits (period)</div><div class="card-v">${{ number_format($fund['periodDeposit'],2) }}</div></td>
            <td><div class="card-l">Withdrawals (period)</div><div class="card-v">${{ number_format($fund['periodWithdrawal'],2) }}</div></td>
            <td><div class="card-l">Profit (period)</div><div class="card-v {{ $fund['periodProfit']<0?'neg':'pos' }}">{{ ($fund['periodProfit']<0?'-':'+') }}${{ number_format(abs($fund['periodProfit']),2) }}</div></td>
        </tr></table>

        {{-- Mutual Fund history: profit/loss distribution + deposits/withdrawals per date --}}
        @if (!empty($fund['transactions']) && count($fund['transactions']))
            <table class="tbl" style="margin-top:8px">
                <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Balance</th></tr></thead>
                <tbody>
                    @foreach ($fund['transactions'] as $t)
                        <tr>
                            <td>{{ $t->created_at->format('d M Y H:i') }}</td>
                            <td>{{ ucfirst($t->type) }}</td>
                            <td class="{{ (float)$t->amount<0?'neg':'pos' }}">{{ ((float)$t->amount<0?'-':'+') }}${{ number_format(abs((float)$t->amount),2) }}</td>
                            <td>${{ number_format((float)$t->balance_after,2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="sub">No mutual-fund transactions in this period.</p>
        @endif
    @endif

    {{-- Spot section (single USD base) --}}
    @foreach (['spot' => 'Spot Trading (USD)'] as $key => $title)
        @php $s = $$key ?? null; @endphp
        @if (!empty($s))
            <div class="sec">{{ $title }}</div>
            <table class="cards"><tr>
                <td><div class="card-l">Wallet balance</div><div class="card-v">{{ $s['cs'] }}{{ number_format($s['balance'],2) }}</div></td>
                <td><div class="card-l">Deposits (period)</div><div class="card-v">{{ $s['cs'] }}{{ number_format($s['deposits'],2) }}</div></td>
                <td><div class="card-l">Withdrawals (period)</div><div class="card-v">{{ $s['cs'] }}{{ number_format($s['withdrawals'],2) }}</div></td>
                <td><div class="card-l">Realized P&L (period)</div><div class="card-v {{ $s['realized']<0?'neg':'pos' }}">{{ ($s['realized']<0?'-':'+') }}{{ $s['cs'] }}{{ number_format(abs($s['realized']),2) }}</div></td>
            </tr></table>
            @if ($s['trades']->count())
                <p class="sub" style="margin:8px 0 2px;font-weight:bold;color:#0a1730">Trade history</p>
                <table class="tbl">
                    <thead><tr><th>Date</th><th>Symbol</th><th>Side</th><th>Qty</th><th>Price</th><th>Value</th><th>Realized P&L</th></tr></thead>
                    <tbody>
                        @foreach ($s['trades'] as $t)
                            <tr>
                                <td>{{ $t->when->format('d M Y H:i') }}</td>
                                <td>{{ $t->symbol }}</td>
                                <td class="{{ $t->side==='Buy'?'pos':'neg' }}">{{ $t->side }}</td>
                                <td>{{ rtrim(rtrim((string)$t->qty,'0'),'.') }}</td>
                                <td>{{ $s['cs'] }}{{ number_format((float)$t->price,2) }}</td>
                                <td>{{ $s['cs'] }}{{ number_format((float)$t->value,2) }}</td>
                                <td class="{{ $t->realized===null ? '' : ($t->realized<0?'neg':'pos') }}">{{ $t->realized===null ? '—' : (($t->realized<0?'-':'+').$s['cs'].number_format(abs($t->realized),2)) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="sub">No spot trades in this period.</p>
            @endif
        @endif
    @endforeach

    <p class="sub" style="margin-top:24px">GrowthCapital Ltd · This statement is generated electronically and is valid without signature.</p>
</div>
</body>
</html>
