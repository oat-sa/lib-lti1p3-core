# PLATFORM - Third-party Initiated Login Creation

> Documentation about how to use, as a Platform, a [3rd party initiated login creation](https://www.imsglobal.org/spec/security/v1p0/#step-1-third-party-initiated-login) service to ease tool interactions.

## Preparing required repository

For this feature, you need to provide first:
-  a [DeploymentRepositoryInterface](../../src/Domain/Deployment/DeploymentRepositoryInterface.php) implementation (to retrieve your deployments configurations)

## Using the service to create preconfigured LTI Links

This library provide the [OidcLoginCreator](../../src/Message/Oidc/OidcLoginCreator.php) that allow you to create a 3rd party initiated login and prepare the redirection to the OIDC login endpoint of the tool.

An usage example:

```php
<?php

use OAT\Library\Lti1p3Core\Platform\OidcLoginCreator;
use OAT\Library\Lti1p3Core\Deployment\DeploymentRepositoryInterface;

// prepare the required repository
/** @var DeploymentRepositoryInterface $deploymentRepository */
$deploymentRepository = ...

$creator = new OidcLoginCreator($deploymentRepository);

// and initialise the tool's 3rd party login initiation service
$loginInitiationRequest = $creator->create(
    'http://platform.com',      // issuer (the platform)
    'login hint',               // login hint for later user authentication om platform side
    'http://tool.com/endpoint'  // The actual tool end-point that should be executed
);

// to finally perform redirection to the OIDC login initiation endpoint of the tool
header('Location: ' . $loginInitiationRequest->buildUrl());
die;
```

**Note**: the provided example is in raw PHP on purpose, to show low coupling to any (framework) HTTP request management. It can be easily replaced with a PSR7 implementation by example.