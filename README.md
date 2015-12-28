# Deployer
[![License](https://poser.pugx.org/mikevrind/deployer/license.svg)](https://packagist.org/packages/mikevrind/deployer)
[![Latest Stable Version](https://poser.pugx.org/mikevrind/deployer/v/stable.svg)](https://packagist.org/packages/mikevrind/deployer)
[![Total Downloads](https://poser.pugx.org/mikevrind/deployer/downloads.svg)](https://packagist.org/packages/mikevrind/deployere)

Use this package to automate the deployment process of your project. Just follow the installation instructions.
Don't forget to check the configuration file for the deployment options!

## Installation

Require this package with composer:

``` 
composer require mikevrind/deployer
```

After updating composer, add the ServiceProvider to the providers array in config/app.php

```
MikeVrind\Deployer\DeployerServiceProvider::class,
```

## Configuration

The setup of the deployer can (and should be) changed by publishing the configuration file.

You _should_ use Artisan to copy the default configuration file from the `/vendor` directory to `/config/deployer.php` with the following command:

```
php artisan vendor:publish --provider="MikeVrind\Deployer\DeployerServiceProvider"
```

It's advised to define the settings via your .env file so that it's possible to change the behavior of the deployer per environment

## CSRF Token
Update the _VerifyCsrfToken_ middleware by adding ```'_deployer/deploy'``` to the ```$except``` array.  
Without this exception, all webhooks will fail because of a missing token in each request.

## .env setup

Add the following lines to your .env(.example) file. The ```DEPLOYER_REMOTE_*``` settings are used to establish a CLI connection with the server. 
``` 
DEPLOYER_ENABLED=true
DEPLOYER_MAIL_ENABLED=true
DEPLOYER_REPO_BRANCH=
DEPLOYER_REPO_PROJECT_ID=
DEPLOYER_REPO_REPOSITORY=

DEPLOYER_REMOTE_HOST=
DEPLOYER_REMOTE_USER=
DEPLOYER_REMOTE_PWD=
DEPLOYER_REMOTE_KEY=
DEPLOYER_REMOTE_KEYTEXT=
DEPLOYER_REMOTE_KEYPHRASE=
```

## Webhook configuration
Open your project git remote set-url originand go to Settings -> Web hooks. This package listens to the ```_deployer/deploy``` route.  
So add something like ```http(s)://www.domain.tld/_deployer/deploy``` as the URL.  
In most cases, the ```Push events``` trigger will be oke to use. Now add the new webhook and you're done!

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any (security related) issues, please create an issue in the the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
