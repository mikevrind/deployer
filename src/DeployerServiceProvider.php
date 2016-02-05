<?php

	namespace MikeVrind\Deployer;

	use Illuminate\Contracts\Foundation\Application;
	use Illuminate\Contracts\Mail\Mailer;
	use Illuminate\Http\Request;
	use Illuminate\Mail\Message;
	use Illuminate\Routing\Router;
	use Illuminate\Support\ServiceProvider;

	class DeployerServiceProvider extends ServiceProvider
	{

		/**
		 * Indicates if loading of the provider is deferred.
		 *
		 * @var bool
		 */
		protected $defer = false;

		/**
		 * Holds an instance of the Mailer object
		 *
		 * @var Mailer
		 */
		private $mailer;

		/**
		 * Holds an instance of the Request object
		 *
		 * @var Request
		 */
		private $request;

		/**
		 * Holds an instance of the Deployer object
		 *
		 * @var Deployer
		 */
		private $deployer;

		/**
		 * Create a new service provider instance.
		 *
		 * @param Application $app
		 * @param Mailer $mailer
		 * @param Request $request
		 * @param Deployer $deployer
		 */
		public function __construct( Application $app, Mailer $mailer, Request $request, Deployer $deployer )
		{
			$this->app      = $app;
			$this->mailer   = $mailer;
			$this->request  = $request;
			$this->deployer = $deployer;
		}

		/**
		 * Register the service provider.
		 *
		 * @return void
		 */
		public function register()
		{
			$configPath = __DIR__ . '/../config/deployer.php';
			$this->mergeConfigFrom( $configPath, 'deployer' );

			$this->app->singleton( 'deployer.deploy', function ( $app )
			{
				return new Console\DeployCommand(
					new Deployer( $this->app, $this->mailer, $this->request )
				);
			} );

			$this->commands( [ 'deployer.deploy' ] );

		}

		/**
		 * Bootstrap the application events.
		 *
		 * @return void
		 */
		public function boot()
		{

			$configPath = __DIR__ . '/../config/deployer.php';
			$this->publishes( [ $configPath => config_path( 'deployer.php' ) ], 'config' );

			$routeConfig = [
				'namespace'  => 'MikeVrind\Deployer\Controllers',
				'prefix'     => '_deployer',
				'middleware' => 'MikeVrind\Deployer\Middleware\DeployerMiddleware',
			];

			$this->getRouter()->group( $routeConfig, function ( $router )
			{
				$router->post( 'deploy', [
					'uses' => 'DeployController@handle',
					'as'   => 'deployer.deployhandler',
				] );

			} );

			# Tell Laravel where to load the views from
			$this->loadViewsFrom( __DIR__ . '/Views', 'deployer' );

		}

		/**
		 * Get the active router.
		 *
		 * @return Router
		 */
		protected function getRouter()
		{
			return $this->app['router'];
		}

		/**
		 * Get the services provided by the provider.
		 *
		 * @return array
		 */
		public function provides()
		{
			return [ 'deployer', 'command.deployer.deploy' ];
		}

	}
