<!DOCTYPE html>
<html>
<body style="margin:0;background:#f4f8fc;font-family:Arial,Helvetica,sans-serif;color:#1a2433">
    <div style="max-width:520px;margin:0 auto;padding:28px">
        <div style="background:#0a1730;border-radius:14px 14px 0 0;padding:24px;text-align:center;color:#fff">
            <h2 style="margin:0;font-size:20px">Growth<span style="color:#16c784">Capital</span></h2>
        </div>
        <div style="background:#fff;border:1px solid #e4e9f0;border-top:0;border-radius:0 0 14px 14px;padding:28px">
            <h3 style="margin:0 0 14px;font-size:18px;color:#0a1730">Your account statement — {{ $data['label'] ?? '' }}</h3>
            <p style="font-size:15px">Hi {{ $data['name'] ?? 'there' }},</p>
            <p style="font-size:15px;color:#5c6b80;line-height:1.5">
                Please find attached your GrowthCapital statement
                @if(!empty($data['start']) && !empty($data['end']))
                    for <strong>{{ $data['start']->format('d M Y') }} – {{ $data['end']->format('d M Y') }}</strong>
                @endif
                — it covers both your <strong>Mutual Fund</strong> and <strong>Spot Trading</strong> activity.
            </p>
            @unless($pdfBytes)
                <p style="font-size:13px;color:#b45309;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px">The detailed PDF could not be attached on the server. Please download your statement from your dashboard.</p>
            @endunless
            <hr style="border:0;border-top:1px solid #e4e9f0;margin:24px 0">
            <p style="font-size:12px;color:#8aa0bd;margin:0">Client ID: {{ $data['code'] ?? '' }}. This is an automated message from an unattended mailbox — please do not reply.</p>
            <p style="font-size:12px;color:#8aa0bd;margin-top:10px">&copy; {{ date('Y') }} GrowthCapital Ltd · All rights reserved.</p>
        </div>
    </div>
</body>
</html>
