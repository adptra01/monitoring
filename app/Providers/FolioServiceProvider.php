<?php

namespace App\Providers;

use Illuminate\Support\Facades\Folio;
use Illuminate\Support\ServiceProvider;

class FolioServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Folio::path(resource_path('views/pages'))
            ->middleware([
                'auth',
                'verified',
                'check.admin',
            ]);
    }
}