# OAuth2 - Authorization Server creation

> Documentation about how to create an authorization server with JWT token as client credentials

## Preparing required repository

For this feature, you need to provide first:
- a [DeploymentRepositoryInterface](../../src/Domain/Deployment/DeploymentRepositoryInterface.php) implementation (to retrieve your deployments configurations)
- a [AccessTokenRepositoryInterface](https://github.com/thephpleague/oauth2-server/blob/master/src/Repositories/AccessTokenRepositoryInterface.php) implementation (to store the created access tokens)
- a [ClientRepositoryInterface](https://github.com/thephpleague/oauth2-server/blob/master/src/Repositories/ClientRepositoryInterface.php) implementation (to retrieve your clients)
- a [ScopeRepositoryInterface](https://github.com/thephpleague/oauth2-server/blob/master/src/Repositories/ScopeRepositoryInterface.php) implementation (to retrieve your scopes)

## Using a factory to create authorization server

This library provides the factory [OAuth2AuthorizationServerFactory](../../src/Service/OAuth2/Factory/OAuth2AuthorizationServerFactory.php) that allows you to create [AuthorizationServer](https://github.com/thephpleague/oauth2-server/blob/master/src/AuthorizationServer.php) with a custom grant [JwtClientCredentialsGrant](../../src/Service/OAuth2/Grant/JwtClientCredentialsGrant.php) and response type [ScopeBearerResponseType](../../src/Service/OAuth2/ResponseType/ScopeBearerResponseType.php).  

An usage example:

```php
<?php

use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use OAT\Library\Lti1p3Core\Deployment\DeploymentRepositoryInterface;
use OAT\Library\Lti1p3Core\Service\OAuth2\Factory\OAuth2AuthorizationServerFactory;

// prepare the required repository
/** @var DeploymentRepositoryInterface $deploymentRepository */
$deploymentRepository = ...

/** @var AccessTokenRepositoryInterface $accessTokenRepository */
$accessTokenRepository = ...

/** @var ClientRepositoryInterface $clientRepository */
$clientRepository = ...

/** @var ScopeRepositoryInterface $scopeRepository */
$scopeRepository = ...

/** @var CryptKey $privateKey */
$privateKey = ...

$factory = new OAuth2AuthorizationServerFactory(
    $deploymentRepository,
    $accessTokenRepository,
    $clientRepository,
    $scopeRepository,
    $privateKey,
    'encrypted key'
);

// and create an authorization server
$authorizationServer = $factory->create();
```
