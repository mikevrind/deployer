<?php

	namespace MikeVrind\Deployer\Controllers;

	use Illuminate\Routing\Controller;
	use MikeVrind\Deployer\Deployer;
	use Monolog\Logger;

	class DeployController extends Controller
	{

		/**
		 * Parse the incoming webhook to the deployer
		 *
		 * @param Deployer $deployer
		 *
		 * @return mixed
		 */
		public function handle( Deployer $deployer )
		{
			$deployMessage = 'Deployment successful';
			$deployStatus  = 200;

			if( !$deployer->deploy() )
			{
				$deployMessage = $deployer->getErrorMessage();
				$deployStatus  = 503;
			}

			if( config( 'deployer.debug', false ) )
			{
				( new Logger() )->addInfo( $deployMessage );
			}

			return response()->json( [
				'status'  => $deployStatus,
				'message' => $deployMessage
			], $deployStatus );
		}

	}