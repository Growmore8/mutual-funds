<?php

namespace App\Console\Commands;

use App\Mail\StatementMail;
use App\Models\User;
use App\Services\StatementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendStatements extends Command
{
    protected $signature = 'statements:send {period : weekly|monthly}';

    protected $description = 'Email every client their PDF statement for the previous week or month.';

    public function handle(StatementService $svc): int
    {
        $period = $this->argument('period');
        [$start, $end, $label] = $svc->previousPeriod($period);

        $sent = 0;
        User::where('role', 'client')->whereNotNull('email')->chunkById(100, function ($clients) use ($svc, $start, $end, $label, &$sent) {
            foreach ($clients as $client) {
                $data = $svc->data($client, $start, $end, $label);
                $pdf = $svc->pdf($data);
                try {
                    Mail::to($client->email)->send(new StatementMail($data, $pdf?->output()));
                    $sent++;
                } catch (\Throwable $e) {
                    Log::warning('Statement email failed for ' . $client->email . ': ' . $e->getMessage());
                }
            }
        });

        $this->info("Sent {$sent} {$period} statement(s) for {$label}.");

        return self::SUCCESS;
    }
}
