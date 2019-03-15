<?php

namespace Saritasa\LaravelRepositories;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use Saritasa\LaravelRepositories\Contracts\IRepositoryFactory;
use Saritasa\LaravelRepositories\Repositories\RepositoryFactory;

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

    /**
     * Make package settings needed to correct work.
     *
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->publishes(
            [
                __DIR__ . '/../config/laravel_repositories.php' =>
                    $this->app->make('path.config') . DIRECTORY_SEPARATOR . 'laravel_repositories.php',
            ],
            'laravel_repositories'
        );
        $this->mergeConfigFrom(__DIR__ . '/../config/laravel_repositories.php', 'laravel_repositories');

        $this->registerCustomBindings();
    }

    /**
     * Register custom repositories implementations.
     *
     * @return void
     *
     * @throws BindingResolutionException
     */
    protected function registerCustomBindings(): void
    {
        $repositoryFactory = $this->app->make(IRepositoryFactory::class);

        foreach (config('laravel_repositories.bindings') as $className => $repository) {
            $repositoryFactory->register($className, $repository);
        }
    }
}
