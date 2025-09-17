<?php

use App\Providers\RepositoryServiceProvider;
use Livewire\LivewireServiceProvider;

return [
    App\Providers\AppServiceProvider::class,
    RepositoryServiceProvider::class,
    LivewireServiceProvider::class
];
