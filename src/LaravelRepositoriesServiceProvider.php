<?php

namespace Saritasa\LaravelRepositories;

use Illuminate\Support\ServiceProvider;
use Saritasa\LaravelRepositories\Repositories\RepositoryFactory;
use Saritasa\LaravelRepositories\Contracts\IRepositoryFactory;

class LaravelRepositoriesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IRepositoryFactory::class, RepositoryFactory::class);
    }
}
