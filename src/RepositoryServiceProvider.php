<?php

namespace Saritasa;

use Illuminate\Support\ServiceProvider;
use Saritasa\Repositories\Eloquent\EloquentRepositoryFactory;
use Saritasa\Contracts\IRepositoryFactory;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IRepositoryFactory::class, EloquentRepositoryFactory::class);
    }
}
