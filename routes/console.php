<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('oyl:deliver-due')
    ->dailyAt('08:00')
    ->withoutOverlapping();
