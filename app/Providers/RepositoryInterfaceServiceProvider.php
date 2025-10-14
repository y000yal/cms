<?php

namespace App\Providers;

use App\Interfaces\BaseInterface;
use App\Repositories\BaseRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryInterfaceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind( BaseInterface::class, BaseRepository::class );

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
