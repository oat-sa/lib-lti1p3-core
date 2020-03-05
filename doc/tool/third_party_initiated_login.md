# TOOL - Third-party Initiated Login

> Documentation about how to expose, as a Tool, a [3rd party initiated login end point](https://www.imsglobal.org/spec/security/v1p0/#step-1-third-party-initiated-login).

## Preparing required repositories

For this feature, you need to provide first:
-  a [DeploymentRepositoryInterface](../../src/Deployment/DeploymentRepositoryInterface.php) implementation to retrieve your deployments configurations
-  a [NonceRepositoryInterface](../../src/Security/Nonce/NonceRepositoryInterface.php) implementation, to save and retrieve security nonces

## Using the service and expose it as an endpoint

This library provide the [OidcLoginInitiator](../../src/Tool/OidcLoginInitiator.php) that allow you to initiate a 3rd party login and prepare the redirection to the OIDC authentication endpoint of the platform.

An usage example:

```php
<?php

use OAT\Library\Lti1p3Core\Tool\OidcLoginInitiator;
use OAT\Library\Lti1p3Core\Deployment\DeploymentRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\LoginInitiationParameters;

// prepare the required repositories
/** @var DeploymentRepositoryInterface $deploymentRepository */
$deploymentRepository = ...

/** @var NonceRepositoryInterface $nonceRepository */
$nonceRepository = ...

$initiator = new OidcLoginInitiator($deploymentRepository, $nonceRepository);

// and initialise the tool's 3rd party login initiation service
$authenticationRequest = $initiator->initiate(new LoginInitiationParameters(
    $_GET['iss'],
    $_GET['login_hint'],
    $_GET['target_link_uri']
));

// to finally perform redirection to the OIDC authentication endpoint of the platform
header('Location: ' . $authenticationRequest->buildUrl());
die;
```

**Note**: the provided example is in raw PHP on purpose, to show low coupling to any (framework) HTTP request management. It can be easily replaced with a PSR7 implementation by example.