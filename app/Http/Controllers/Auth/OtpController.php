<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OtpController extends Controller
{
    public function show(Request $request)
    {
        if ($request->user()->otp_verified_at) {
            return redirect()->route('kyc.show');
        }

        return view('auth.verify-otp', ['email' => $request->user()->email]);
    }

    public function verify(Request $request, OtpService $otp)
    {
        $request->validate(['code' => ['required', 'string']]);

        $user = $request->user();

        if (! $otp->verify($user->email, $request->code)) {
            return back()->withErrors(['code' => 'Invalid or expired code. Please try again.']);
        }

        $user->forceFill([
            'otp_verified_at' => now(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return redirect()->route('kyc.show')->with('status', 'Email verified successfully.');
    }

    public function resend(Request $request, OtpService $otp)
    {
        $user = $request->user();
        $otp->issue($user->email, $user->name);

        return back()->with('status', 'A new code has been sent to your email.');
    }
}
