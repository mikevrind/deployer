<?php

	namespace MikeVrind\Deployer;

	use Collective\Remote\RemoteManager;
	use Illuminate\Contracts\Foundation\Application;
	use Illuminate\Contracts\Mail\Mailer;
	use Illuminate\Http\Request;
	use Illuminate\Mail\Message;
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
		 *
		 * @var Request
		 */
		private $request;

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

			# Set the config values for the remote package
			$this->app['config']->set( 'remote.connections.production.host', $this->app['config']->get( 'deployer.remote_connection.host' ) );
			$this->app['config']->set( 'remote.connections.production.username', $this->app['config']->get( 'deployer.remote_connection.username' ) );
			$this->app['config']->set( 'remote.connections.production.password', $this->app['config']->get( 'deployer.remote_connection.password' ) );
			$this->app['config']->set( 'remote.connections.production.key', $this->app['config']->get( 'deployer.remote_connection.key' ) );
			$this->app['config']->set( 'remote.connections.production.keytext', $this->app['config']->get( 'deployer.remote_connection.keytext' ) );
			$this->app['config']->set( 'remote.connections.production.keyphrase', $this->app['config']->get( 'deployer.remote_connection.keyphrase' ) );
			$this->app['config']->set( 'remote.connections.production.timeout', $this->app['config']->get( 'deployer.remote_connection.timeout' ) );
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
			$commands         = $this->app['config']->get( 'deployer.commands' );

			# Check if there are any commands to execute
			if( empty( $commands ) )
			{
				return $this->setErrorMessage( 'No commands were given to execute' );
			}

			# Try to execute the commands
			try
			{

				$remoteConnection->run( $this->app['config']->get( 'deployer.commands' ), function ( $line ) use ( &$cliResponse )
				{
					$cliResponse[] = str_replace( ' ', '&nbsp;', $line );
				} );

				# Check if we need to e-mail the output
				if( $this->app['config']->get( 'deployer.mail.enabled' ) and count( $this->app['config']->get( 'deployer.mail.recipient' ) ) > 0 )
				{
					$this->mailOutput( $cliResponse );
				}


				return true;


			}
			catch( RuntimeException $e )
			{
				return $this->setErrorMessage( $e->getMessage() );
			}

		}

		/**
		 * Mail the output of the CLI to the given users
		 *
		 * @param $cliResponse
		 * @return mixed
		 */
		private function mailOutput( $cliResponse )
		{

			return $this->mailer->send( 'deployer::deployment', [ 'data' => $cliResponse ], function ( Message $message )
			{

				$developers = $this->app['config']->get( 'deployer.mail.recipient' );

				$message->to( $developers[0] );
				unset( $developers[0] );

				foreach( $developers as $developer )
				{
					$message->cc( $developer );
				}

				$message->subject(
					$this->request->input( 'repository.name', 'Unknown project' ) .
					' [' . $this->app['config']->get( 'deployer.repository.branch' ) . '] has been deployed by ' .
					$this->request->input( 'user_name', 'John Doe' )
				);

			} );
		}

		/**
		 * Return the error message
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
