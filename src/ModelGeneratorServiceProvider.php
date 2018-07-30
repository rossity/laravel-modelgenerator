<?php

namespace Rossity\ModelGenerator;

use Illuminate\Support\ServiceProvider;
use Rossity\ModelGenerator\Console\GenerateAllCommand;
use Rossity\ModelGenerator\Console\GenerateModelCommand;
use Rossity\ModelGenerator\Console\GenerateTemplateCommand;

class ModelGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateTemplateCommand::class,
                GenerateModelCommand::class,
                GenerateAllCommand::class,
            ]);
        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

    }
}
