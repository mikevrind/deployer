<?php

	namespace MikeVrind\Deployer\Controllers;

	use Illuminate\Routing\Controller;
	use MikeVrind\Deployer\Deployer;

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
			if( $deployer->deploy() )
			{

				return response()->json( [
					'status'  => 200,
					'message' => 'Deployment successful'
				], 200 );

			}

			return response()->json( [
				'status'  => 503,
				'message' => $deployer->getErrorMessage()
			], 503 );
		}

	}