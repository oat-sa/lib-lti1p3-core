# OAuth authorization server

> Documentation about how to create an authorization server with JWT token as client credentials

## Table of contents

- [Preparing required repository](#preparing-required-repository)
- [Create an authorization server](#create-an-authorization-server)
- [Generate access tokens](#generate-access-tokens)

## Preparing required repository

For this feature, you need to provide first:
- a [DeploymentRepositoryInterface](../../src/Domain/Deployment/DeploymentRepositoryInterface.php) implementation (to retrieve your deployments configurations)
- a [AccessTokenRepositoryInterface](https://github.com/thephpleague/oauth2-server/blob/master/src/Repositories/AccessTokenRepositoryInterface.php) implementation (to store the created access tokens)
- a [ClientRepositoryInterface](https://github.com/thephpleague/oauth2-server/blob/master/src/Repositories/ClientRepositoryInterface.php) implementation (to retrieve your clients)
- a [ScopeRepositoryInterface](https://github.com/thephpleague/oauth2-server/blob/master/src/Repositories/ScopeRepositoryInterface.php) implementation (to retrieve your scopes)

## Create an authorization server

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

## Generate access tokens

This library provides [OAuth2AccessTokenGenerator](../../src/Service/OAuth2/OAuth2AccessTokenGenerator.php) that allows you to generate an access token.

For using this service you need to provide the server instance created on previous step.

```php
<?php

use League\OAuth2\Server\AuthorizationServer;
use OAT\Library\Lti1p3Core\Service\OAuth2\Factory\OAuth2AuthorizationServerFactory;
use OAT\Library\Lti1p3Core\Service\OAuth2\OAuth2AccessTokenGenerator;use Psr\Http\Message\ResponseInterface;use Psr\Http\Message\ServerRequestInterface;

// prepare the required authorization server
/** @var AuthorizationServer $authorizationServer */
$authorizationServer = (new OAuth2AuthorizationServerFactory(/* ... */))->create();

// create a generator instance
$generator = new OAuth2AccessTokenGenerator($authorizationServer);

/** @var ServerRequestInterface $psr7Request */
$psr7Request = ...

/** @var ResponseInterface $psrResponse */
$psrResponse = ...

// and generate an access token
$accessToken = $generator->generate($psr7Request, $psrResponse);
``` 
