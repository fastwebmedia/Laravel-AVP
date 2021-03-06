<?php namespace FWM\LaravelAVP;

use Illuminate\Support\ServiceProvider;

class LaravelAVPServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
        $this->publishes([
            __DIR__ . '/config/laravel-avp.php' => config_path('laravel-avp.php'),
        ]);

        $this->loadViewsFrom(__DIR__ . '/views', 'laravel-avp');

        $this->publishes([
            __DIR__ . '/views' => base_path('resources/views/vendor/laravel-avp'),
        ]);

        $this->loadTranslationsFrom(__DIR__ . '/lang', 'laravel-avp');

		include_once __DIR__.'/LaravelAVPFilter.php';
		include_once __DIR__.'/../../macros.php';
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}
