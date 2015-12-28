<?php

	namespace MikeVrind\Deployer\Controllers;

	use Illuminate\Routing\Controller;
	use MikeVrind\Deployer\Deployer;

	class deployController extends Controller
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
				return response('Deployment successful', 200);
			}
			else
			{
				return response($deployer->getErrorMessage(), 503);
			}

		}

	}