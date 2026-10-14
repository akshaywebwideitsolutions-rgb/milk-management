<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune')->daily();
if (! config('nativephp-internal.running')) {
    Schedule::command('billing:generate-due')->dailyAt('00:10')->withoutOverlapping();
}
