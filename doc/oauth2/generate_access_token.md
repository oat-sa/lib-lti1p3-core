# Generation of  Access Token

> Documentation about how to generate an access token using generator service

## Using a service OAuth2AccessTokenGenerator

This library provides [OAuth2AccessTokenGenerator](../../src/Service/OAuth2/OAuth2AccessTokenGenerator.php) that allows you to generate an access token.

For using this service you need to prepare authorization server (you can use [OAuth2AuthorizationServerFactory](create_authorization_server.md)).

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
