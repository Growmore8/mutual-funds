<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Statement · {{ $client->name }}</title>
    <style>
        *{box-sizing:border-box}
        body{font-family:Arial,Helvetica,sans-serif;color:#1a2433;margin:0;background:#f4f8fc;padding:24px}
        .sheet{max-width:780px;margin:0 auto;background:#fff;border:1px solid #e4e9f0;border-radius:10px;overflow:hidden}
        .head{background:#0a1730;color:#fff;padding:20px 24px;display:flex;justify-content:space-between;align-items:center}
        .head h1{margin:0;font-size:18px}
        .head .brand b{color:#16c784}
        .meta{padding:20px 24px;display:grid;grid-template-columns:1fr 1fr;gap:10px 24px;font-size:13px}
        .meta div span{color:#7a8aa0}
        .meta div b{display:block;color:#0a1730;font-size:14px}
        table{width:100%;border-collapse:collapse;font-size:13px}
        th,td{text-align:left;padding:9px 24px;border-bottom:1px solid #eef2f7}
        th{background:#f7f9fc;color:#7a8aa0}
        td.r,th.r{text-align:right}
        .pos{color:#0a9d56}
        .neg{color:#dc2626}
        .foot{padding:16px 24px;font-size:11px;color:#8aa0bd}
        .toolbar{max-width:780px;margin:0 auto 14px;text-align:right}
        .btn{background:#16c784;color:#04231a;border:0;padding:9px 18px;border-radius:8px;font-weight:700;cursor:pointer;text-decoration:none}
        @media print{.toolbar{display:none}body{background:#fff;padding:0}.sheet{border:0}}
    </style>
</head>
<body>
    <div class="toolbar"><button class="btn" onclick="window.print()">Print / Save as PDF</button></div>
    <div class="sheet">
        <div class="head">
            <div class="brand"><h1>Growth<b>Capital</b> · Statement</h1></div>
            <div style="text-align:right;font-size:12px">{{ $client->clientCode() }}<br>{{ now()->format('d M Y') }}</div>
        </div>
        <div class="meta">
            <div><span>Client</span><b>{{ $client->name }}</b></div>
            <div><span>Email</span><b>{{ $client->email }}</b></div>
            <div><span>Join date</span><b>{{ $client->created_at->format('d M Y') }}</b></div>
            <div><span>Pool / Live ID</span><b>{{ $client->poolAccount->account_ref ?? '—' }}</b></div>
            <div><span>Plan</span><b>{{ $client->accountType->name ?? '—' }}</b></div>
            <div><span>KYC</span><b>{{ ucfirst(str_replace('_',' ',$client->kyc_status)) }}</b></div>
            <div><span>Capital (Balance)</span><b>${{ number_format($client->totalDeposited(),2) }}</b></div>
            <div><span>Running PnL</span><b>${{ number_format($client->runningPnl(),2) }}</b></div>
            <div><span>Withdrawable</span><b>${{ number_format($client->availableToWithdraw(),2) }}</b></div>
        </div>
        <table>
            <thead><tr><th>Date</th><th>Type</th><th>Description</th><th class="r">Amount</th><th class="r">Balance</th></tr></thead>
            <tbody>
                @forelse ($transactions as $t)
                    <tr>
                        <td>{{ $t->created_at->format('d M Y') }}</td>
                        <td>{{ ucfirst($t->type) }}</td>
                        <td>{{ $t->description ?? '—' }}</td>
                        <td class="r {{ $t->amount < 0 ? 'neg' : 'pos' }}">{{ ($t->amount<0?'':'+') . '$' . number_format((float)$t->amount,2) }}</td>
                        <td class="r">${{ number_format((float)$t->balance_after,2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;color:#8aa0bd;padding:24px">No transactions.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="foot">GrowthCapital Ltd · License 11064258 · This statement is generated electronically.</div>
    </div>
</body>
</html>
