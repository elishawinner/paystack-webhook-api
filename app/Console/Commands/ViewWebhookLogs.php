<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ViewWebhookLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'app:view-webhook-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    // public function handle()
    // {
    //     //
    // }
protected $signature = 'webhook:logs {--limit=20 : Number of logs to display}';

public function handle()
{
    $logs = WebhookLog::latest()
        ->limit($this->option('limit'))
        ->get();

    $this->table(
        ['ID', 'Event', 'Reference', 'Verified', 'Time'],
        $logs->map(function ($log) {
            return [
                $log->id,
                $log->event_type,
                $log->reference,
                $log->is_verified ? '✅' : '❌',
                $log->created_at->diffForHumans()
            ];
        })
    );
}
    
}
