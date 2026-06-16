<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AccountType;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register', [
            'accountTypes' => AccountType::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request, OtpService $otp): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],          // Full name as per National ID
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'max:40'],
            'country' => ['required', 'string', 'max:100'],
            'account_type_id' => ['required', 'exists:account_types,id'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'country' => $request->country,
            'account_type_id' => $request->account_type_id,
            'password' => Hash::make($request->password),
            'role' => 'client',
            'status' => 'pending',
            'kyc_status' => 'not_submitted',
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Send the email verification OTP, then take the user to the verify step.
        $otp->issue($user->email, $user->name);

        return redirect()->route('otp.show');
    }
}
