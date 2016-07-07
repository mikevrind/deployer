<?php

	namespace MikeVrind\Deployer;

	use Collective\Remote\RemoteManager;
	use Illuminate\Config\Repository as Config;
	use Illuminate\Contracts\Foundation\Application;
	use Illuminate\Contracts\Mail\Mailer;
	use Illuminate\Http\Request;
	use Illuminate\Mail\Message;
	use RuntimeException;

	class Deployer extends RemoteManager
	{
		/**
		 * The commands to run on the remote connection
		 *
		 * @var array
		 */
		protected $commands;

		/**
		 * The tasks to run on the remote connection
		 *
		 * @var array
		 */
		protected $tasks;

		/**
		 * @var \Collective\Remote\ConnectionInterface
		 */
		protected $remoteConnection;

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
		 * @param Mailer                                         $mailer
		 * @param Request                                        $request
		 */
		public function __construct( Application $app, Mailer $mailer, Request $request )
		{
			$this->app                   = $app;
			$this->mailer                = $mailer;
			$this->request               = $request;
			$this->config                = $this->app['config'];
			$this->basePath              = $this->config->get( 'deployer.base_path', base_path() );
			$this->commands              = $this->config->get( 'deployer.commands', [ ] );
			$this->tasks                 = $this->config->get( 'deployer.tasks', [ ] );
			$this->tasksMailOnCompletion = $this->config->get( 'deployer.tasks_mail_on_completed', false );

			# Set the config values for the remote package
			$this->config->set( 'remote.connections.production.host', $this->config->get( 'deployer.remote_connection.host' ) );
			$this->config->set( 'remote.connections.production.username', $this->config->get( 'deployer.remote_connection.username' ) );
			$this->config->set( 'remote.connections.production.password', $this->config->get( 'deployer.remote_connection.password' ) );
			$this->config->set( 'remote.connections.production.key', $this->config->get( 'deployer.remote_connection.key' ) );
			$this->config->set( 'remote.connections.production.keytext', $this->config->get( 'deployer.remote_connection.keytext' ) );
			$this->config->set( 'remote.connections.production.keyphrase', $this->config->get( 'deployer.remote_connection.keyphrase' ) );
			$this->config->set( 'remote.connections.production.timeout', $this->config->get( 'deployer.remote_connection.timeout' ) );

			# Set remote connection after configuration has been set
			$this->remoteConnection = $this->into( 'production' );
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
		 * Run the deployment process
		 *
		 * @return bool
		 */
		public function deploy()
		{
			$commandResult = null;
			$taskResult    = null;

			# Check if there are any commands or tasks configured to execute
			if( empty( $this->commands ) && empty( $this->tasks ) )
			{
				$this->setErrorMessage( 'No commands or tasks configured to execute' );

				# Check if we need to e-mail the output
				if( $this->isMailEnabled() )
				{
					$this->mailFailed();
				}
			}

			if( !empty( $this->commands ) )
			{
				$commandResult = $this->runCommands();
			}

			if( !empty( $this->tasks ) )
			{
				$taskResult = $this->runTasks();
			}

			return ( is_null( $commandResult ) || $commandResult ) && ( is_null( $taskResult ) || $taskResult );
		}

		/**
		 * @param $line
		 *
		 * @return mixed
		 */
		private function parseResponse( $line )
		{
			return str_replace( ' ', '&nbsp;', $line );
		}

		/**
		 * Run all commands
		 *
		 * @return bool
		 */
		private function runCommands()
		{
			$self        = $this;
			$cliResponse = [ ];

			# Try to execute the commands
			try
			{
				# Add command to task to change working directory
				array_unshift( $this->commands, 'cd ' . $this->basePath );

				# Run commands
				$this->remoteConnection->run( $this->commands, function ( $line ) use ( $self, &$cliResponse )
				{
					$cliResponse[] = $self->parseResponse( $line );
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
				$this->setErrorMessage( $e->getMessage() );

				# Check if we need to e-mail the output
				if( $this->isMailEnabled() )
				{
					$this->mailFailed();
				}
			}

			return false;
		}

		/**
		 * Run all tasks
		 *
		 * @return bool
		 */
		private function runTasks()
		{
			$self = $this;

			$allTasksResponse = [ ];
			$t                = 1;
			$tasksCount       = count( $this->tasks );
			$tasksCompleted   = 0;
			foreach( $this->tasks as $task => $properties )
			{
				# Skip tasks that have no commands
				if( empty( $properties['commands'] ) )
				{
					continue;
				}

				# Empty task response
				$taskResponse = [ ];

				# Add command to task to change working directory
				array_unshift( $properties['commands'], 'cd ' . $this->basePath );

				# Define the commands for this task
				$this->remoteConnection->define( $task, $properties['commands'] );

				try
				{
					# Run the commands for this task
					$this->remoteConnection->task( $task, function ( $line ) use ( $self, &$taskResponse )
					{
						$taskResponse[] = $self->parseResponse( $line );
					} );

					# Check if we need to e-mail the output of the task
					if( $this->isMailEnabled() && $properties['mail'] )
					{
						$this->mailTaskSuccess( $task, $t, $tasksCount, $taskResponse );
					}

					$tasksCompleted++;
				}
				catch( RuntimeException $e )
				{
					$this->setErrorMessage( $e->getMessage() );

					# Add error message to response
					$taskResponse[] = $self->parseResponse( $e->getMessage() );

					# Check if we need to e-mail the output
					if( $this->isMailEnabled() )
					{
						$this->mailTaskFailed( $task, $t, $tasksCount );
					}
				}

				# Save this tasks response
				$allTasksResponse = array_merge( $allTasksResponse, $taskResponse );

				$t++;
			}

			# Mail response when all tasks have been completed
			if( $tasksCompleted > 0 && $this->isMailEnabled() && $this->tasksMailOnCompletion )
			{
				$this->mailSuccess( $allTasksResponse );
			}

			return $tasksCompleted > 0;
		}

		/**
		 * Mail the CLI output
		 *
		 * @param array $response
		 *
		 * @return mixed
		 */
		private function mailSuccess( array $response )
		{
			return $this->mailOutput(
				$response,
				# Unknown project [master] as been deployed by John Doe
				$this->getRepositoryName() . ' [' . $this->getDeployedBranch() . '] has been deployed by ' . $this->getDeployedBy()
			);
		}

		/**
		 * Mail the CLI output
		 *
		 * @param string $task
		 * @param int    $current
		 * @param int    $max
		 * @param array  $response
		 *
		 * @return mixed
		 */
		private function mailTaskSuccess( $task, $current, $max, array $response )
		{
			return $this->mailOutput(
				$response,
				# Unknown project [master] task TASK [1 of 1] has been completed
				$this->getRepositoryName() . ' [' . $this->getDeployedBranch() . '] task ' . $task . ' [' . $current . ' of ' . $max . '] has been completed!'
			);
		}

		/**
		 * Mail the error message
		 *
		 * @param array $response
		 *
		 * @return mixed
		 */
		private function mailFailed( array $response = [ ] )
		{
			if( empty( $response ) )
			{
				$response = $this->errorMessage;
			}

			return $this->mailOutput(
				$response,
				# Unknown project [master] could not be deployed
				$this->getRepositoryName() . ' [' . $this->getDeployedBranch() . '] could not be deployed!'
			);
		}

		/**
		 * Mail the error message
		 *
		 * @param string $task
		 * @param int    $current
		 * @param int    $max
		 * @param array  $response
		 *
		 * @return mixed
		 */
		private function mailTaskFailed( $task, $current, $max, array $response = [ ] )
		{
			if( empty( $response ) )
			{
				$response = $this->errorMessage;
			}

			return $this->mailOutput(
				$response,
				# Unknown project [master] task TASK [1 of 1] could not be completed
				$this->getRepositoryName() . ' [' . $this->getDeployedBranch() . '] task ' . $task . ' [' . $current . ' of ' . $max . '] could not be completed!'
			);
		}

		/**
		 * Mail the output of the CLI to the given users
		 *
		 * @param $cliResponse
		 * @param $subject
		 *
		 * @return mixed
		 */
		private function mailOutput( array $cliResponse, $subject )
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
		 * Set the error message
		 *
		 * @param $value
		 *
		 * @return bool
		 */
		public function setErrorMessage( $value )
		{
			$this->errorMessage = $value;

			return false;
		}

	}
