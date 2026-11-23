<?php

use App\Models\Banner;
use App\Models\Listing;
use App\Models\Subscription;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
 * Housekeeping. On cPanel add ONE cron job (every minute or every 5 minutes):
 *   /usr/local/bin/php /home/USER/4ulk/artisan schedule:run >> /dev/null 2>&1
 * The site still works without cron: expired ads are hidden by query scopes,
 * this just keeps the status columns tidy.
 */
Artisan::command('marketplace:housekeeping', function () {
    $ads = Listing::where('status', 'active')->where('expires_at', '<=', now())->update(['status' => 'expired']);
    $subs = Subscription::where('status', 'active')->where('ends_at', '<=', now())->update(['status' => 'expired']);
    $banners = Banner::where('status', 'active')->whereDate('ends_at', '<', today())->update(['status' => 'expired']);
    $this->info("Expired {$ads} ads, {$subs} subscriptions, {$banners} banners.");
})->purpose('Expire old ads, subscriptions and banners');

Schedule::command('marketplace:housekeeping')->hourly();
