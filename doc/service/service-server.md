# OAuth authorization server

> How to set up an OAuth2 authorization server endpoint to protect your platforms LTI service endpoints.

## Table of contents

- [Preparation of the required dependencies](#preparation-of-the-required-dependencies)
- [Generation of an access token response for a registration](#generation-of-an-access-token-response-for-a-registration)
- [Validation of an access token request](#validation-of-an-access-token-request)

## Preparation of the required dependencies

This library allow you to easily expose a OAuth2 server for a given subscription, to protect your LTI service calls as a platform.

You can reuse this feature in several endpoints of your application to serve several servers for distinct registrations (client id).

The server feature rely on the [PHP League OAuth2 server](https://oauth2.thephpleague.com/), therefore you need to provide:
- a [ClientRepositoryInterface](https://github.com/thephpleague/oauth2-server/blob/master/src/Repositories/ClientRepositoryInterface.php) implementation (to retrieve and validate your clients)
- a [AccessTokenRepositoryInterface](https://github.com/thephpleague/oauth2-server/blob/master/src/Repositories/AccessTokenRepositoryInterface.php) implementation (to store the created access tokens)
- a [ScopeRepositoryInterface](https://github.com/thephpleague/oauth2-server/blob/master/src/Repositories/ScopeRepositoryInterface.php) implementation (to retrieve your scopes)

or you can simply use the available [library repositories](../../src/Service/Server/Repository) for this.

Your will also need to provide an encryption key (random string with enough entropy).

## Generation of an access token response for a registration

This library provides a ready to use [AccessTokenGenerator](../../src/Service/Server/Generator/AccessTokenResponseGenerator.php) to generate access tokens responses for a registration:
- it requires a registration repository implementation [as explained here](../quickstart/interfaces.md) to automate signature logic against your platform registration private key
- it complies to the `client_credentials` grant type with `client_assertion` to follow [IMS security specifications](https://www.imsglobal.org/spec/security/v1p0/#using-json-web-tokens-with-oauth-2-0-client-credentials-grant)
- it expects a [PSR7 ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface), a [PSR7 ResponseInterface](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface) and a registration identifier to be easily exposed behind any PSR7 compliant controller

For example, to expose an LTI service server your application endpoint `[POST] /lti/auth/{registrationIdentifier}/token`:

```php
<?php

use League\OAuth2\Server\Exception\OAuthServerException;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Service\Server\Generator\AccessTokenResponseGenerator;
use OAT\Library\Lti1p3Core\Service\Server\Factory\AuthorizationServerFactory;
use OAT\Library\Lti1p3Core\Service\Server\Repository\AccessTokenRepository;
use OAT\Library\Lti1p3Core\Service\Server\Repository\ClientRepository;
use OAT\Library\Lti1p3Core\Service\Server\Repository\ScopeRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$factory = new AuthorizationServerFactory(
    new ClientRepository(...),
    new AccessTokenRepository(...),
    new ScopeRepository(...),
    'superSecretEncryptionKey' // You obviously have to add more entropy, this is an example
);

/** @var RegistrationRepositoryInterface $repository */
$repository = ...

$generator = new AccessTokenResponseGenerator($repository, $factory);

/** @var ServerRequestInterface $request */
$request = ...

/** @var ResponseInterface $response */
$response = ...

try {
    // Extract registrationIdentifier from request uri parameter
    $registrationIdentifier = ...

    // Validate, generate and sign access token response, using as the registration platform private key
    return $generator->generate($request, $response, $registrationIdentifier);

} catch (OAuthServerException $exception) {
    return $exception->generateHttpResponse($response);
}
``` 

## Validation of an access token request

Once a tool has been granted with an access token, it can perform LTI service authenticated calls (with header `Authorization: Bearer <token>`).

To be able to protect your platform endpoints, you can use the provided [AccessTokenRequestValidator](../../src/Service/Server/Validator/AccessTokenRequestValidator.php):
- it requires a registration repository implementation [as explained here](../quickstart/interfaces.md) to automate the token signature checks
- it expects a [PSR7 ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) to validate
- and it will output a [AccessTokenRequestValidationResult](../../src/Service/Server/Validator/AccessTokenRequestValidationResult.php) representing the token validation and the token itself.

For example,
```php
<?php

use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Service\Server\Validator\AccessTokenRequestValidator;
use Psr\Http\Message\ServerRequestInterface;

/** @var RegistrationRepositoryInterface $repository */
$repository = ...

$validator = new AccessTokenRequestValidator($repository);

/** @var ServerRequestInterface $request */
$request = ...

// Validate access token using as the registration platform public key
$result = $validator->validate($request);

// Result exploitation
if (!$result->hasFailures()) {
    var_dump($result->getToken()->getClaims());
} 
```