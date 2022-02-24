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
use TromsFylkestrafikk\Camera\Jobs\DetectStale;
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
        $this->app->booted(function () {
            // @var \Illuminate\Console\Scheduling\Schedule $schedule
            $schedule = $this->app->make(Schedule::class);
            $scanPeriod = config('camera.poor_mans_inotify');
            if ($scanPeriod) {
                $schedule->command(FindLatest::class)->cron(sprintf("*/%d * * * *", $scanPeriod));
            }
            $schedule->job(DetectStale::class)->everyMinute();
        });
    }

    /**
     * Setup routes utilized by camera.
     */
    protected function registerRoutes()
    {
        $prefixes = ['api' => 'api', 'web' => ''];
        foreach (['api', 'web'] as $group) {
            $routeAttrs = config(
                "camera.route_attributes.$group",
                ['prefix' => $prefixes[$group], 'middleware' => [$group]]
            );
            Route::group($routeAttrs, function () use ($group) {
                $this->loadRoutesFrom(__DIR__ . "/../routes/{$group}.php");
            });
        }
    }
}
