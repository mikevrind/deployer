<?php

	return [

		/*
		|--------------------------------------------------------------------------
		| Deployer settings
		|--------------------------------------------------------------------------
		|
		| Deployer is disabled by default to prevent unintentional deployments.
		| You can override the value by setting enable to true instead of false.
		| It's encouraged to define these values in your .env file.
		|
		*/

		'enabled' => env( 'DEPLOYER_ENABLED', false ),

		/*
		|--------------------------------------------------------------------------
		| Deploy commands
		|--------------------------------------------------------------------------
		|
		| List all the commands that should be executed when deploying.
		|
		*/

		'commands' => [

			# Browse to the project root folder
			'cd ' . base_path(),

			# Pull changes from the correct branch
			'git pull origin ' . env( 'DEPLOYER_REPO_BRANCH', 'master' ),

			# Install dependencies according to the composer.lock file
			'composer install --no-interaction',

			# Force to run the database migrations
			'php artisan migrate --force'

		],

		/*
		|--------------------------------------------------------------------------
		| E-mail settings
		|--------------------------------------------------------------------------
		|
		| E-mailing the CLI output is disabled by default. You can override the
		| value by setting enable to true instead of false. When this option
		| is enabled, all output will be e-mailed to the given recipients.
		|
		*/

		'mail' => [

			'enabled' => env( 'DEPLOYER_MAIL_ENABLED', false ),

			'recipient' => [

				'user@domain.tld',

			],
		],

		/*
		|--------------------------------------------------------------------------
		| Repository settings
		|--------------------------------------------------------------------------
		|
		| Provide all information about the repository from where you are deploying.
		| All incoming webhooks will be validated against this information.
		|
		*/

		'repository' => [

			'branch'     => env( 'DEPLOYER_REPO_BRANCH', 'master' ),
			'project_id' => env( 'DEPLOYER_REPO_PROJECT_ID', 1 ),
			'repository' => env( 'DEPLOYER_REPO_REPOSITORY', 'git@git.domain.tld/group/project.git' ),

		],

		/*
		|--------------------------------------------------------------------------
		| Remote login
		|--------------------------------------------------------------------------
		|
		| The deployer requires an account to login on a remote server to execute
		| all commands provided earlier.
		|
		*/

		'remote_connection' => [
			'host'      => env( 'DEPLOYER_REMOTE_HOST', '' ),
			'username'  => env( 'DEPLOYER_REMOTE_USER', '' ),
			'password'  => env( 'DEPLOYER_REMOTE_PWD', '' ),
			'key'       => env( 'DEPLOYER_REMOTE_KEY', '' ),
			'keytext'   => env( 'DEPLOYER_REMOTE_KEYTEXT', '' ),
			'keyphrase' => env( 'DEPLOYER_REMOTE_KEYPHRASE', '' ),
			'timeout'   => env( 'DEPLOYER_REMOTE_TIMEOUT', 120 ),
		],

	];