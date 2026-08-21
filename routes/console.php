<?php

use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Schedule;

AboutCommand::add('Dr. Sam', fn () => [
    'Revision passwordless' => config('drsam.review_passwordless') ? 'enabled' : 'disabled',
    'Runtime architecture' => 'Laravel native',
]);

Schedule::command('cbta:sync-mixtures --limit=50')
    ->everyMinute()
    ->withoutOverlapping();
