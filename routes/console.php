<?php

use Illuminate\Support\Facades\Schedule;


Schedule::command('app:cek-kedaluwarsa')->dailyAt('08:00');
