<?php

namespace Imagewize\SageNativeBlock\Providers;

use Illuminate\Support\ServiceProvider;
use Imagewize\SageNativeBlock\Console\ExampleCommand;
use Imagewize\SageNativeBlock\Example;

class SageNativeBlockServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('SageNativeBlock', function () {
            return new Example($this->app);
        });

        $this->mergeConfigFrom(
            __DIR__.'/../../config/sage-native-block.php',
            'sage-native-block'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/sage-native-block.php' => $this->app->configPath('sage-native-block.php'),
        ], 'config');

        $this->loadViewsFrom(
            __DIR__.'/../../resources/views',
            'SageNativeBlock',
        );

        $this->commands([
            SageNativeBlockCommand::class,
        ]);

        $this->app->make('SageNativeBlock');
    }
}
