<?php namespace MikeVrind\Deployer\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;

class DeployerMiddleware
{

	/**
	 * The Laravel application instance.
	 *
	 * @var \Illuminate\Foundation\Application
	 */
	protected $app;

	/**
	 * @param Application $app
	 */
	public function __construct( Application $app )
	{
		$this->app = $app;
	}

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  \Closure                 $next
	 *
	 * @return mixed
	 */
	public function handle( $request, Closure $next )
	{

		if( $this->app['config']->get( 'deployer.enabled' ) )
		{

			# Validate the request
			if( $this->isValidRequest( $request ) )
			{

				return $next( $request );
			}

			return response( 'The incoming request was invalid', 401 );

		}

		return response( 'The deployer is not enabled', 503 );

	}

	/**
	 * Validate the incoming post request
	 *
	 * @param $request
	 *
	 * @return bool
	 */
	private function isValidRequest( $request )
	{

		return

			$request->input( 'ref' ) == 'refs/heads/' . $this->app['config']->get( 'deployer.repository.branch' ) and
			$request->input( 'project_id' ) == $this->app['config']->get( 'deployer.repository.project_id' ) and
			$request->input( 'repository.url' ) == $this->app['config']->get( 'deployer.repository.repository' );

	}

}