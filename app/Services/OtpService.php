<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public function issue(string $email, string $name = '', string $purpose = 'email_verification'): OtpCode
    {
        // Invalidate any previous unconsumed codes for this email/purpose.
        OtpCode::where('email', $email)->where('purpose', $purpose)->whereNull('consumed_at')->delete();

        $code = (string) random_int(100000, 999999);

        $otp = OtpCode::create([
            'email' => $email,
            'code' => $code,
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(10),
        ]);

        // Always log it (so it is testable before SMTP is verified).
        Log::info("OTP for {$email} [{$purpose}]: {$code}");

        // Attempt to email it; never block the flow if mail fails.
        try {
            Mail::to($email)->send(new OtpMail($code, $name));
        } catch (\Throwable $e) {
            Log::warning('OTP email failed: ' . $e->getMessage());
        }

        return $otp;
    }

    public function verify(string $email, string $code, string $purpose = 'email_verification'): bool
    {
        $otp = OtpCode::where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest()
            ->first();

        if (! $otp || ! $otp->isValid()) {
            return false;
        }

        $otp->increment('attempts');

        if (! hash_equals($otp->code, trim($code))) {
            return false;
        }

        $otp->update(['consumed_at' => now()]);

        return true;
    }
}
