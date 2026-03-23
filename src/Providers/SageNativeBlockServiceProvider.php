<?php

namespace Imagewize\SageNativeBlockPackage\Providers;

use Illuminate\Support\ServiceProvider;
use Imagewize\SageNativeBlockPackage\Console\SageNativeBlockAddSetupCommand;
use Imagewize\SageNativeBlockPackage\Console\SageNativeBlockCommand;

class SageNativeBlockServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
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

        $this->commands([
            SageNativeBlockCommand::class,
            SageNativeBlockAddSetupCommand::class,
        ]);
    }
}
