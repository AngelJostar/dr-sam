<?php

use Illuminate\Foundation\Console\AboutCommand;

AboutCommand::add('Dr. Sam', fn () => [
    'Revision passwordless' => config('drsam.review_passwordless') ? 'enabled' : 'disabled',
    'Runtime architecture' => 'Laravel native',
]);
