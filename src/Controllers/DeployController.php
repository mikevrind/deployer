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
		 * @return mixed
		 */
		public function handle(Deployer $deployer)
		{

			if ( $deployer->deploy() )
			{

				return response([
					'status' => 200,
					'message' => 'deployment successful'
				], 200);

			}
			else
			{
				return response([
					'status' => 503,
					'message' => $deployer->getErrorMessage()
				], 503);

			}

		}

	}