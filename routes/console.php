<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('payments:expire')
    ->everyMinute();
