<?php

	namespace MikeVrind\Deployer\Console;

	use Illuminate\Console\Command;
	use MikeVrind\Deployer\Deployer;

	class DeployCommand extends Command
	{
		/**
		 * The name and signature of the console command.
		 *
		 * @var string
		 */
		protected $signature = 'deployer:deploy';

		/**
		 * The console command description.
		 *
		 * @var string
		 */
		protected $description = 'Manually execute the deploy commands without a webhook';

		/**
		 * Holds an instance of the actual Deployer
		 *
		 * @var Deployer
		 */
		protected $deployer;

		/**
		 * @param Deployer $deployer
		 */
		public function __construct( Deployer $deployer )
		{
			$this->deployer = $deployer;

			parent::__construct();
		}

		/**
		 * Execute the deployer
		 *
		 * @return void
		 */
		public function fire()
		{

			if( $this->confirm( 'Do you wish to manually run the deployer commands?' ) )
			{

				if( $this->deployer->deploy() )
				{
					$this->info( 'Deployment successful' );
					return;
				}

				$this->error( $this->deployer->getErrorMessage() );
			}

		}

	}
