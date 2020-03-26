# Library interfaces

> Depending on the top level services you want to use, you have to provide your own implementation of the following interfaces.

## Deployment repository

In order to be able to retrieve your deployments from your configuration storage, you need to provide an implementation of the [DeploymentRepositoryInterface](../../src/Deployment/DeploymentRepositoryInterface.php).

By example:
```php
<?php

use OAT\Library\Lti1p3Core\Deployment\DeploymentInterface;
use OAT\Library\Lti1p3Core\Deployment\DeploymentRepositoryInterface;

$deploymentRepository = new class implements DeploymentRepositoryInterface
{
   public function find(string $identifier): ?DeploymentInterface
   {
       // TODO: Implement find() method to find a deployment by identifier, or null if not found.
   }

   public function findByIssuer(string $issuer, string $clientId = null): ?DeploymentInterface
   {
        // TODO: Implement findByIssuer() method to find a deployment by issuer, and client id if provided.
   }
};
```
**Note**: you can find a simple implementation example of this interface in the method `createTestDeploymentRepository()` of the [DomainTestingTrait](../../tests/Traits/DomainTestingTrait.php).

## User authenticator

During the [OIDC authentication handling](https://www.imsglobal.org/spec/security/v1p0#step-3-authentication-response) on the platform side, you need to define how to delegate the user authentication by providing an implementation of the [UserAuthenticatorInterface](../../src/Security/User/UserAuthenticatorInterface.php).

By example:
```php
<?php

use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticationResultInterface;

$userAuthenticator = new class implements UserAuthenticatorInterface
{
   public function authenticate(string $loginHint): UserAuthenticationResultInterface
   {
       // TODO: Implement authenticate() method to perform user authentication (ex: ldap, auth server, session, etc)
   }
};
```
**Notes**:
- you can find a simple implementation example of this interface in the method `createTestUserAuthenticator()` of the [SecurityTestingTrait](../../tests/Traits/SecurityTestingTrait.php).
- you can find a ready to use `UserAuthenticationResultInterface` implementation is available in [UserAuthenticationResult](../../src/Security/User/UserAuthenticationResult.php)

## Nonce repository

In order to be able to store security nonce the way you want, you need to provide an implementation of the [NonceRepositoryInterface](../../src/Security/Nonce/NonceRepositoryInterface.php).

By example:
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
**Note**: you can find a simple implementation example of this interface in the method `createTestNonceRepository()` of the [SecurityTestingTrait](../../tests/Traits/SecurityTestingTrait.php).

## JWKS fetcher

In order to be able to fetch public keys JWK from configured [JWKS endpoint](https://auth0.com/docs/tokens/concepts/jwks), you need to provide an implementation of the [JwksFetcherInterface](../../src/Security/Jwks/Fetcher/JwksFetcherInterface.php).

By example:
```php
<?php

use OAT\Library\Lti1p3Core\Security\Jwks\Fetcher\JwksFetcherInterface;
use Lcobucci\JWT\Signer\Key;

$fetcher = new class implements JwksFetcherInterface
{
    public function fetchKey(string $jwksUrl,string $kId) : Key
    {
        // TODO: Implement fetchKey() method to find a Key via an HTTP call to the $jwksUrl, for the kid $kId.
    }
};
```
**Notes**:
- it is recommended to put in cache the JWKS endpoint responses, to improve performances since they dont change often. Your implementation can then rely on an injected PSR6 cache by example.
- you can find a ready to use implementation in [GuzzleJwksFetcher](../../src/Security/Jwks/Fetcher/GuzzleJwksFetcher.php): you need to provide it a [guzzle](http://docs.guzzlephp.org/en/stable/) client, with enabled [cache middleware](https://github.com/Kevinrob/guzzle-cache-middleware).