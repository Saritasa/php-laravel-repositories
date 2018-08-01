<?php

namespace Saritasa\LaravelRepositories;

use Illuminate\Support\ServiceProvider;
use Saritasa\LaravelRepositories\Repositories\RepositoryFactory;
use Saritasa\LaravelRepositories\Contracts\IRepositoryFactory;

/**
 * Package providers. Registers package implementation in DI container.
 * Make settings needed to correct work.
 */
class LaravelRepositoriesServiceProvider extends ServiceProvider
{
    /**
     * Register package implementation in DI container.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(IRepositoryFactory::class, RepositoryFactory::class);
    }
}
