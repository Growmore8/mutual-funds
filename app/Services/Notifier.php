<?php

namespace App\Services;

use App\Mail\NoReplyMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Notifier
{
    /**
     * Send a branded no-reply transactional email. Failures are logged, never
     * thrown — a mail problem must not break the action that triggered it.
     *
     * @param  array<int,string>  $lines
     */
    public static function send(
        User $user,
        string $subject,
        string $heading,
        array $lines,
        ?string $actionUrl = null,
        ?string $actionText = null,
    ): void {
        try {
            Mail::to($user->email)->send(
                new NoReplyMail($subject, $heading, $lines, $user->name, $actionUrl, $actionText)
            );
        } catch (\Throwable $e) {
            Log::warning('Notifier email failed: ' . $e->getMessage(), ['user' => $user->id, 'subject' => $subject]);
        }
    }
}
