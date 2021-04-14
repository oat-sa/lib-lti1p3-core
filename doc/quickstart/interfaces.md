# Library interfaces

> Depending on the top level services you want to use, you have to provide your own implementation of the following interfaces.

## Table of contents

- [Mandatory interfaces](#mandatory-interfaces)
- [Optional interfaces](#optional-interfaces)

## Mandatory interfaces

This section present the mandatory interfaces from the library to be implemented to use provided services.

### Registration repository interface

**Required by**:
- [Message](../../src/Message)
- [Service](../../src/Service)

In order to be able to retrieve your registrations from your configuration storage, you need to provide an implementation of the [RegistrationRepositoryInterface](../../src/Registration/RegistrationRepositoryInterface.php).

For example:
```php
<?php

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;

$registrationRepository = new class implements RegistrationRepositoryInterface
{
   public function find(string $identifier): ?RegistrationInterface
   {
       // TODO: Implement find() method to find a registration by identifier, or null if not found.
   }

   public function findAll(): array
   {
       // TODO: Implement findAll() method to find all available registrations.
   }

   public function findByClientId(string $clientId) : ?RegistrationInterface
   {
       // TODO: Implement findByClientId() method to find a registration by client id, or null if not found.
   }

   public function findByPlatformIssuer(string $issuer, string $clientId = null): ?RegistrationInterface
   {
        // TODO: Implement findByPlatformIssuer() method to find a registration by platform issuer, and client id if provided.
   }

   public function findByToolIssuer(string $issuer, string $clientId = null): ?RegistrationInterface
   {
        // TODO: Implement findByToolIssuer() method to find a registration by tool issuer, and client id if provided.
   }
};
```
**Note**: you can find a simple implementation example of this interface in the method `createTestRegistrationRepository()` of the [DomainTestingTrait](../../tests/Traits/DomainTestingTrait.php).

### User authenticator interface

**Required by**: [Message](../../src/Message)  

During the [OIDC authentication handling](https://www.imsglobal.org/spec/security/v1p0#step-3-authentication-response) on the platform side, you need to define how to delegate the user authentication by providing an implementation of the [UserAuthenticatorInterface](../../src/Security/User/UserAuthenticatorInterface.php).

For example:

```php
<?php

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use OAT\Library\Lti1p3Core\Security\User\Result\UserAuthenticationResultInterface;

$userAuthenticator = new class implements UserAuthenticatorInterface
{
   public function authenticate(
       RegistrationInterface $registration,
       string $loginHint
   ): UserAuthenticationResultInterface {
       // TODO: Implement authenticate() method to perform user authentication (ex: session, LDAP, etc)
   }
};
```
**Notes**:
- you can find a simple implementation example of this interface in the method `createTestUserAuthenticator()` of the [SecurityTestingTrait](../../tests/Traits/SecurityTestingTrait.php).
- you can find a ready to use `UserAuthenticationResultInterface` implementation is available in [UserAuthenticationResult](../../src/Security/User/Result/UserAuthenticationResult.php)

## Optional interfaces

This section present the optional interfaces from the library you can implement, but for which a default implementation is already provided.

### Nonce repository interface

**Default implementation**: [NonceRepository](../../src/Security/Nonce/NonceRepository.php)

In order to be able to store security nonce the way you want, you can provide an implementation of the [NonceRepositoryInterface](../../src/Security/Nonce/NonceRepositoryInterface.php).

For example:
```php
<?php

use OAT\Library\Lti1p3Core\Security\Nonce\NonceInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceRepositoryInterface;

$nonceRepository = new class implements NonceRepositoryInterface
{
    public function find(string $value) : ?NonceInterface
    {
        // TODO: Implement find() method to find a nonce by value, or null if not found.
    }

    public function save(NonceInterface $nonce) : void
    {
        // TODO: Implement save() method to save a nonce (cache, database, etc)
    }
};
```
**Note**: the ready to use [NonceRepository](../../src/Security/Nonce/NonceRepository.php) works with a [PSR6 cache](https://www.php-fig.org/psr/psr-6/#cacheitempoolinterface).

### JWKS fetcher interface

**Default implementation**: [JwksFetcher](../../src/Security/Jwks/Fetcher/JwksFetcher.php)

In order to be able to fetch public keys JWK from configured [JWKS endpoint](https://auth0.com/docs/tokens/concepts/jwks), you need to provide an implementation of the [JwksFetcherInterface](../../src/Security/Jwks/Fetcher/JwksFetcherInterface.php).

For example:
```php
<?php

use OAT\Library\Lti1p3Core\Security\Jwks\Fetcher\JwksFetcherInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;

$fetcher = new class implements JwksFetcherInterface
{
    public function fetchKey(string $jwksUrl, string $kId) : KeyInterface
    {
        // TODO: Implement fetchKey() method to find a Key via an HTTP call to the $jwksUrl, for the kid $kId.
    }
};
```
**Notes**:
- it is recommended to put in cache the JWKS endpoint responses, to improve performances since they don't change often. Your implementation can then rely on a cache by example.
- the ready to use [JwksFetcher](../../src/Security/Jwks/Fetcher/JwksFetcher.php) works with a [guzzle](http://docs.guzzlephp.org/en/stable/) client to request JWKS data, a [PSR6 cache](https://www.php-fig.org/psr/psr-6/#cacheitempoolinterface) to cache them, and a [PSR3 logger](https://www.php-fig.org/psr/psr-3/#3-psrlogloggerinterface) to log this process.

### LTI platform message launch validator interface

**Default implementation**: [PlatformLaunchValidator](../../src/Message/Launch/Validator/Platform/PlatformLaunchValidator.php)

To customise platform message launch validation, an implementation of the [PlatformLaunchValidatorInterface](../../src/Message/Launch/Validator/Platform/PlatformLaunchValidatorInterface.php) can be provided.

### LTI tool message launch validator interface

**Default implementation**: [ToolLaunchValidator](../../src/Message/Launch/Validator/Tool/ToolLaunchValidator.php)

To customise tool message launch validation, an implementation of the [ToolLaunchValidatorInterface](../../src/Message/Launch/Validator/Tool/ToolLaunchValidatorInterface.php) can be provided.

### LTI service client interface

**Default implementation**: [LtiServiceClient](../../src/Service/Client/LtiServiceClient.php) 

In order to send authenticated service calls, an implementation of the [LtiServiceClientInterface](../../src/Service/Client/LtiServiceClientInterface.php) can be provided.

For example:

```php
<?php

use OAT\Library\Lti1p3Core\Service\Client\LtiServiceClientInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;  
use Psr\Http\Message\ResponseInterface;

$client = new class implements LtiServiceClientInterface
{
    public function request(RegistrationInterface $registration, string $method, string $uri, array $options = [], array $scopes = []) : ResponseInterface
    {
        // TODO: Implement request() method to manage authenticated calls to services.
    }
};
```        
**Notes**:                                                                                                                                                                                                                                                                            
- it is recommended to put in cache the service access tokens, to improve performances. Your implementation can then rely on an injected [PSR6 cache](https://www.php-fig.org/psr/psr-6/#cacheitempoolinterface) by example.

### LTI service server access token generator interface

**Default implementation**: [AccessTokenResponseGenerator](../../src/Security/OAuth2/Generator/AccessTokenResponseGenerator.php)

To customise access token generation, an implementation of the [AccessTokenResponseGeneratorInterface](../../src/Security/OAuth2/Generator/AccessTokenResponseGeneratorInterface.php) can be provided.

### LTI service server access token validator interface

**Default implementation**: [RequestAccessTokenValidator](../../src/Security/OAuth2/Validator/RequestAccessTokenValidator.php)

To customise access token validation, an implementation of the [RequestAccessTokenValidatorInterface](../../src/Security/OAuth2/Validator/RequestAccessTokenValidatorInterface.php) can be provided.

### LTI service server client repository interface

**Default implementation**: [ClientRepository](../../src/Security/OAuth2/Repository/ClientRepository.php)  

In order to retrieve and validate clients involved in authenticated service calls, an implementation of the [ClientRepositoryInterface](https://github.com/thephpleague/oauth2-server/blob/master/src/Repositories/ClientRepositoryInterface.php) can be provided.

**Notes**:
- the default `ClientRepository` injects the `RegistrationRepositoryInterface` to be able to expose your platforms as oauth2 providers and tools as consumers.
- in case of the consumer tool public key is not given in the registration, it will automatically fallback to a JWKS call.

### LTI service server access token repository interface

**Default implementation**: [AccessTokenRepository](../../src/Security/OAuth2/Repository/AccessTokenRepository.php)  

In order to store service calls access tokens, an implementation of the [AccessTokenRepositoryInterface](https://github.com/thephpleague/oauth2-server/blob/master/src/Repositories/AccessTokenRepositoryInterface.php) can be provided.

**Note**: the default `AccessTokenRepository` implementation rely on a [PSR6 cache](https://www.php-fig.org/psr/psr-6/#cacheitempoolinterface) to store generated access tokens.

### LTI service server scope repository interface

**Default implementation**: [ScopeRepository](../../src/Security/OAuth2/Repository/ScopeRepository.php)  

In order to retrieve and finalize scopes during grants, an implementation of the [ScopeRepositoryInterface](https://github.com/thephpleague/oauth2-server/blob/master/src/Repositories/ScopeRepositoryInterface.php) can be provided.

**Note**:
- the default `ScopeRepository` will just provide back scopes given at construction.

### Id generator interface

**Default implementation**: [IdGenerator](../../src/Util/Generator/IdGenerator.php)

To customise overall id generation, an implementation of the [IdGeneratorInterface](../../src/Util/Generator/IdGeneratorInterface.php) can be provided.

**Note**:
- the default `IdGenerator` generates [UUIDv4](https://en.wikipedia.org/wiki/Universally_unique_identifier#Version_4_(random)).

### JWT interface

**Default implementation**: [Token](../../src/Security/Jwt/Token.php)

To customise JWT handling, an implementation of the [TokenInterface](../../src/Security/Jwt/TokenInterface.php) can be provided.

### JWT builder interface

**Default implementation**: [Builder](../../src/Security/Jwt/Builder/Builder.php)

To customise JWT creation, an implementation of the [BuilderInterface](../../src/Security/Jwt/Builder/BuilderInterface.php) can be provided.

### JWT parser interface

**Default implementation**: [Parser](../../src/Security/Jwt/Parser/Parser.php)

To customise JWT parsing, an implementation of the [ParserInterface](../../src/Security/Jwt/Parser/ParserInterface.php) can be provided.

### JWT validator interface

**Default implementation**: [Validator](../../src/Security/Jwt/Validator/Validator.php)

To customise JWT validation, an implementation of the [ValidatorInterface](../../src/Security/Jwt/Validator/ValidatorInterface.php) can be provided.