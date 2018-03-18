<?php

namespace Railken\Laravel\Manager;

use Illuminate\Support\ServiceProvider;

class ManagerServiceProvider extends ServiceProvider
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    public $app;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([Commands\Generate::class, Commands\GenerateAttribute::class]);
    }
}
