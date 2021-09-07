<?php

namespace TromsFylkestrafikk\Camera;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use TromsFylkestrafikk\Camera\Console\CameraAdd;
use TromsFylkestrafikk\Camera\Console\CameraList;
use TromsFylkestrafikk\Camera\Console\CameraRemove;
use TromsFylkestrafikk\Camera\Console\CameraSet;
use TromsFylkestrafikk\Camera\Console\CameraShow;
use TromsFylkestrafikk\Camera\Console\FolderWatcher;
use TromsFylkestrafikk\Camera\Console\FindLatest;
use TromsFylkestrafikk\Camera\Services\CameraTokenizer;

class CameraServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->registerMigrations();
        $this->registerConfig();
        $this->registerConsoleCommands();
        $this->registerScheduledCommands();
        $this->registerRoutes();
    }

    public function register()
    {
        $this->app->singleton(CameraTokenizer::class, function () {
            return new CameraTokenizer(['id', 'camera_id', 'name', 'ip', 'mac', 'latitude', 'longitude']);
        });
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
                FindLatest::class,
                FolderWatcher::class,
            ]);
        }
    }

    protected function registerScheduledCommands()
    {
        $scanPeriod = config('camera.poor_mans_inotify');
        if (!$scanPeriod) {
            return;
        }
        $this->app->booted(function () use ($scanPeriod) {
            // @var \Illuminate\Console\Scheduling\Schedule $schedule
            $schedule = $this->app->make(Schedule::class);
            $schedule->command(FindLatest::class)->cron(sprintf("*/%d * * * *", $scanPeriod));
        });
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
