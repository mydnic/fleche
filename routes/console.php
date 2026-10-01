<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('fleche:generate')->hourly();
Schedule::command('fleche:prune')->dailyAt('03:00');
