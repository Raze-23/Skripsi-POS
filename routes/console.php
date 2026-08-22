<?php

use Illuminate\Support\Facades\Schedule;


Schedule::command('app:cek-produk')->hourly();