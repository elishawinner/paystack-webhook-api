<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Notification;
use App\Notifications\WebhookReceived;
use App\Models\WebhookLog;



class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
   public function boot()
{
    WebhookLog::created(function ($log) {
        if (config('app.env') === 'production') {
            Notification::route('slack', config('services.slack.webhook_url'))
                ->notify(new WebhookReceived($log));
        }
    });
}
}
