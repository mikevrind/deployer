<?php

	namespace MikeVrind\Deployer;

	use Collective\Remote\RemoteManager;
	use Illuminate\Contracts\Foundation\Application;
	use Illuminate\Contracts\Mail\Mailer;
	use Illuminate\Http\Request;
	use Illuminate\Mail\Message;
	use Illuminate\Config\Repository as Config;
	use RuntimeException;

	class Deployer extends RemoteManager
	{

		/**
		 * Hold the error message
		 *
		 * @var
		 */
		private $errorMessage;

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
		 * Holds an instance of the Config object
		 *
		 * @var Config
		 */
		private $config;

		/**
		 * Create a new remote manager instance.
		 *
		 * @param Application|\Illuminate\Foundation\Application $app
		 * @param Mailer $mailer
		 * @param Request $request
		 */
		public function __construct( Application $app, Mailer $mailer, Request $request )
		{
			$this->app     = $app;
			$this->mailer  = $mailer;
			$this->request = $request;
			$this->config  = $this->app['config'];

			# Set the config values for the remote package
			$this->config->set( 'remote.connections.production.host', $this->config->get( 'deployer.remote_connection.host' ) );
			$this->config->set( 'remote.connections.production.username', $this->config->get( 'deployer.remote_connection.username' ) );
			$this->config->set( 'remote.connections.production.password', $this->config->get( 'deployer.remote_connection.password' ) );
			$this->config->set( 'remote.connections.production.key', $this->config->get( 'deployer.remote_connection.key' ) );
			$this->config->set( 'remote.connections.production.keytext', $this->config->get( 'deployer.remote_connection.keytext' ) );
			$this->config->set( 'remote.connections.production.keyphrase', $this->config->get( 'deployer.remote_connection.keyphrase' ) );
			$this->config->set( 'remote.connections.production.timeout', $this->config->get( 'deployer.remote_connection.timeout' ) );
		}

		/**
		 * Run the deployment process
		 *
		 * @return bool
		 */
		public function deploy()
		{

			$cliResponse      = [ ];
			$remoteConnection = $this->into( 'production' );
			$commands         = $this->config->get( 'deployer.commands' );

			# Check if there are any commands to execute
			if( empty( $commands ) )
			{
				return $this->setErrorMessage( 'No commands were given to execute' );
			}

			# Try to execute the commands
			try
			{

				$remoteConnection->run( $this->config->get( 'deployer.commands' ), function ( $line ) use ( &$cliResponse )
				{
					$cliResponse[] = str_replace( ' ', '&nbsp;', $line );
				} );

				# Check if we need to e-mail the output
				if( $this->isMailEnabled() )
				{
					$this->mailSuccess( $cliResponse );
				}

				return true;
			}
			catch( RuntimeException $e )
			{
				# Check if we need to e-mail the output
				if( $this->isMailEnabled() )
				{
					$this->mailFailed( $e->getMessage() );
				}

				return $this->setErrorMessage( $e->getMessage() );
			}
		}

		/**
		 * Mail the CLI output
		 *
		 * @param array $response
		 * @return mixed
		 */
		private function mailSuccess( array $response )
		{
			return $this->mailOutput(
				$response,
				$this->getRepositoryName() . ' [' . $this->getDeployedBranch() . '] has been deployed by ' . $this->getDeployedBy()
			);
		}

		/**
		 * Mail the error message
		 *
		 * @param array $response
		 * @return mixed
		 */
		private function mailFailed( array $response )
		{
			return $this->mailOutput(
				$response,
				$this->getRepositoryName() . ' [' . $this->getDeployedBranch() . '] could not be deployed!'
			);
		}

		/**
		 * Mail the output of the CLI to the given users
		 *
		 * @param $cliResponse
		 * @return mixed
		 */
		private function mailOutput( $cliResponse, $subject )
		{

			return $this->mailer->send( 'deployer::deployment', [ 'data' => $cliResponse ], function ( Message $message ) use ( $subject )
			{

				$developers = $this->config->get( 'deployer.mail.recipient' );

				$message->to( $developers[0] );
				unset( $developers[0] );

				foreach( $developers as $developer )
				{
					$message->cc( $developer );
				}

				$message->subject( $subject );

			} );
		}

		/**
		 * Check configuration if mail is enabled and we have at least one recipient
		 *
		 * @return bool
		 */
		private function isMailEnabled()
		{
			return ( $this->config->get( 'deployer.mail.enabled' ) and count( $this->config->get( 'deployer.mail.recipient' ) ) > 0 );
		}

		/**
		 * Get the branch we try to deploy
		 *
		 * @return mixed
		 */
		private function getDeployedBranch()
		{
			return $this->config->get( 'deployer.repository.branch' );
		}

		/**
		 * Get the name of the repository we try to deploy
		 *
		 * @return mixed
		 */
		private function getRepositoryName()
		{
			return $this->request->input( 'repository.name', 'Unknown project' );
		}

		/**
		 * Get the name of who is trying to deploy
		 *
		 * @return mixed
		 */
		private function getDeployedBy()
		{
			return $this->request->input( 'user_name', 'John Doe' );
		}

		/**
		 * Get the error message
		 *
		 * @return mixed
		 */
		public function getErrorMessage()
		{
			return $this->errorMessage;
		}

		/**
		 * Set the error message
		 *
		 * @param $value
		 * @return bool
		 */
		public function setErrorMessage( $value )
		{
			$this->errorMessage = $value;

			return false;
		}

	}
