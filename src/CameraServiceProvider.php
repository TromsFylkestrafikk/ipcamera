<?php

namespace TromsFylkestrafikk\Camera;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use TromsFylkestrafikk\Camera\Console\CameraAdd;
use TromsFylkestrafikk\Camera\Console\CameraList;
use TromsFylkestrafikk\Camera\Console\CameraRemove;
use TromsFylkestrafikk\Camera\Console\CameraSet;
use TromsFylkestrafikk\Camera\Console\CameraShow;

class CameraServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->registerMigrations();
        $this->registerConfig();
        $this->registerConsoleCommands();
        $this->registerRoutes();
    }

    /**
     * Setup migrations
     */
    protected function registerMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function registerConfig()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/camera.php' => config_path('camera.php')
            ], 'config');
        }
    }

    /**
     * Setup Artisan console commands.
     */
    protected function registerConsoleCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CameraAdd::class,
                CameraList::class,
                CameraRemove::class,
                CameraSet::class,
                CameraShow::class,
            ]);
        }
    }

    /**
     * Setup routes utilized by camera.
     */
    protected function registerRoutes()
    {
        $routeAttrs = config('camera.route_attributes', ['prefix' => '', 'middleware' => ['api']]);
        Route::group($routeAttrs, function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        });
    }
}
