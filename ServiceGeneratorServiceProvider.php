<?php

namespace OkayBueno\ServiceGenerator;

use Illuminate\Support\ServiceProvider;

/**
 * Class ServiceGeneratorServiceProvider
 * @package OkayBueno\ServiceGenerator
 */
class ServiceGeneratorServiceProvider extends ServiceProvider
{

    private $configPath = '/config/service-generator.php';


    /**
     *
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.$this->configPath => config_path('service-generator.php'),
        ], 'service-generator');
    }


    /**
     *
     */
    public function register()
    {
        // merge default config
        $this->mergeConfigFrom(
            __DIR__.$this->configPath , 'repositories'
        );

        // And generators.
        $this->registerServiceGenerator();
    }


    /**
     *
     */
    private function registerServiceGenerator()
    {
        $this->app->singleton('command.service', function ($app)
        {
            return $app['OkayBueno\ServiceGenerator\Commands\MakeServiceCommand'];
        });

        $this->commands('command.service');
    }

}