<!DOCTYPE html>
<html>
<body style="margin:0;background:#f4f8fc;font-family:Arial,Helvetica,sans-serif;color:#1a2433">
    <div style="max-width:520px;margin:0 auto;padding:28px">
        <div style="background:#0a1730;border-radius:14px;padding:24px;text-align:center;color:#fff">
            <h2 style="margin:0;font-size:20px">Growth<span style="color:#16c784">Capital</span> — Mutual Funds</h2>
        </div>
        <div style="background:#fff;border:1px solid #e4e9f0;border-top:0;border-radius:0 0 14px 14px;padding:28px;text-align:center">
            <p style="font-size:15px">Hi{{ $name ? ' '.$name : '' }},</p>
            <p style="font-size:15px;color:#5c6b80">Use the verification code below to confirm your email address. It expires in 10 minutes.</p>
            <div style="font-size:34px;font-weight:800;letter-spacing:8px;color:#0a1730;background:#f4f8fc;border-radius:10px;padding:16px;margin:18px 0">{{ $code }}</div>
            <p style="font-size:13px;color:#8aa0bd">If you didn't request this, you can ignore this email.</p>
            <p style="font-size:12px;color:#8aa0bd;margin-top:24px">&copy; {{ date('Y') }} GrowthCapital Ltd · License 11064258</p>
        </div>
    </div>
</body>
</html>
