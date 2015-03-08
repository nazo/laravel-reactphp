<?php namespace LaravelReactPHP\Providers;

use Illuminate\Support\ServiceProvider;
use LaravelReactPHP\Console\Commands\ReactServe;

class ReactCommandProvider extends ServiceProvider {

	protected $commands = ['ReactServe' => 'command.react-serve'];

	public function boot()
	{

	}

	public function register()
	{
		$this->app->singleton('command.react-serve', function()
		{
			return new ReactServe();
		});
		$this->commands('command.react-serve');
	}
}
