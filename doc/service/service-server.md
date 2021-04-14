# LTI service server

> How to set up an OAuth2 authorization server endpoint to protect your platforms LTI service endpoints.

## Table of contents

- [Preparation of required dependencies](#preparation-of-required-dependencies)
- [Generation of access token response for a key chain](#generation-of-access-token-response-for-a-key-chain)
- [Service endpoint authentication](#service-endpoint-authentication)

## Preparation of required dependencies

This library allow you to easily expose a OAuth2 server for a given subscription, to protect your LTI service calls as a platform.

You can reuse this feature in several endpoints of your application to serve several servers for distinct registrations (client id).

The server feature rely on the [PHP League OAuth2 server](https://oauth2.thephpleague.com/), therefore you need to provide:
- a [ClientRepositoryInterface](https://github.com/thephpleague/oauth2-server/blob/master/src/Repositories/ClientRepositoryInterface.php) implementation (to retrieve and validate your clients)
- a [AccessTokenRepositoryInterface](https://github.com/thephpleague/oauth2-server/blob/master/src/Repositories/AccessTokenRepositoryInterface.php) implementation (to store the created access tokens)
- a [ScopeRepositoryInterface](https://github.com/thephpleague/oauth2-server/blob/master/src/Repositories/ScopeRepositoryInterface.php) implementation (to retrieve your scopes)

or you can simply use the available [library repositories](../../src/Security/OAuth2/Repository) for this.

Your will also need to provide an encryption key (random string with enough entropy).

## Generation of access token response for a key chain

This library provides a ready to use [AccessTokenResponseGenerator](../../src/Security/OAuth2/Generator/AccessTokenResponseGenerator.php) to generate access tokens responses for a given key chain:
- it requires a key chain repository implementation [as explained here](../quickstart/interfaces.md) to automate signature logic against a key chain private key
- it complies to the `client_credentials` grant type with `client_assertion` to follow [IMS security specifications](https://www.imsglobal.org/spec/security/v1p0/#using-json-web-tokens-with-oauth-2-0-client-credentials-grant)
- it expects a [PSR7 ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface), a [PSR7 ResponseInterface](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface) and a key chain identifier to be easily exposed behind any PSR7 compliant controller

For example, to expose an LTI service server your application endpoint `[POST] /lti/auth/{keyChainIdentifier}/token`:

```php
<?php

use League\OAuth2\Server\Exception\OAuthServerException;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\OAuth2\Generator\AccessTokenResponseGenerator;
use OAT\Library\Lti1p3Core\Security\OAuth2\Factory\AuthorizationServerFactory;
use OAT\Library\Lti1p3Core\Security\OAuth2\Repository\AccessTokenRepository;
use OAT\Library\Lti1p3Core\Security\OAuth2\Repository\ClientRepository;
use OAT\Library\Lti1p3Core\Security\OAuth2\Repository\ScopeRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$factory = new AuthorizationServerFactory(
    new ClientRepository(...),
    new AccessTokenRepository(...),
    new ScopeRepository(...),
    'superSecretEncryptionKey' // You obviously have to add more entropy, this is an example
);

/** @var KeyChainRepositoryInterface $repository */
$repository = ...

$generator = new AccessTokenResponseGenerator($repository, $factory);

/** @var ServerRequestInterface $request */
$request = ...

/** @var ResponseInterface $response */
$response = ...

try {
    // Extract keyChainIdentifier from request uri parameter
    $keyChainIdentifier = ...

    // Validate assertion, generate and sign access token response, using the key chain private key
    return $generator->generate($request, $response, $keyChainIdentifier);

} catch (OAuthServerException $exception) {
    return $exception->generateHttpResponse($response);
}
``` 

## Service endpoint authentication

Once a tool has been granted with an access token, it can perform LTI service authenticated calls.

(with header `Authorization: Bearer <token>`).

To be able to protect your platform endpoints, you can use the provided [RequestAccessTokenValidator](../../src/Security/OAuth2/Validator/RequestAccessTokenValidator.php):
- it requires a registration repository implementation [as explained here](../quickstart/interfaces.md) to automate the token signature checks
- it expects a [PSR7 ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) to validate
- it will output a [RequestAccessTokenValidationResult](../../src/Security/OAuth2/Validator/Result/RequestAccessTokenValidationResult.php) representing the token validation, the related registration, the token itself and associated scopes.

For example,

```php
<?php

use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\OAuth2\Validator\RequestAccessTokenValidator;
use Psr\Http\Message\ServerRequestInterface;

/** @var RegistrationRepositoryInterface $repository */
$repository = ...

$validator = new RequestAccessTokenValidator($repository);

/** @var ServerRequestInterface $request */
$request = ...

// Validate request provided access token using the registration platform public key, against allowed scopes
$result = $validator->validate($request, ['allowed-scope', 'other-allowed-scope']);

// Result exploitation
if (!$result->hasError()) {
    // You have access to related registration (to spare queries)
    echo $result->getRegistration()->getIdentifier();

    // And to the JWT
    var_dump($result->getToken()->getHeaders()->all()); 
    var_dump($result->getToken()->getClaims()->all()); 
    echo ($result->getToken()->toString()); 

    // And to the oauth2 scopes
    echo $result->getScopes();

    // If needed, you can also access the validation successes
    foreach ($result->getSuccesses() as $success) {
        echo $success;
    }
}
```