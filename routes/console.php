<?php

use Illuminate\Support\Facades\Schedule;


Schedule::command('app:cek-produk')->dailyAt('08:00');
