# Library configuration

> How to provide configuration step by step, to be able to use the library as a tool, or a platform, or both.

## Table of contents

- [Configure security keys](#configure-security-keys)
- [Configure a platform](#configure-a-platform)
- [Configure a tool](#configure-a-tool)
- [Configure a registration](#configure-a-registration)
- [Public key versus JWKS](#public-key-versus-jwks)

## Configure security keys

Whether you act as platform, tool or both, you need to provide security keys to be able to sign the messages that will be exchanged during LTI interactions.

Considering you have for example on your side this key chain:
- public key path: `/home/user/.ssh/id_rsa.pub`
- private key path: `/home/user/.ssh/id_rsa`
- private key passphrase: `test`

You can then use the provided [KeyChainFactory](../../src/Security/Key/KeyChainFactory.php) to build your security keys:

```php
<?php

use OAT\Library\Lti1p3Core\Security\Key\KeyChainFactory;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;

$keyChain = (new KeyChainFactory)->create(
    '1',                                // [required] identifier (used for JWT kid header)
    'mySetName',                        // [required] key set name (for grouping)
    'file://home/user/.ssh/id_rsa.pub', // [required] public key (file or content)
    'file://home/user/.ssh/id_rsa',     // [optional] private key (file or content)
    'test',                             // [optional] private key passphrase (if existing)
     KeyInterface::ALG_RS256            // [optional] algorithm (default: RS256)
);
```
**Notes**:
- given example deals with local key files, automatically done when prefixed by `file://`
- you can provide the public / private key stream content or [JWK array values](https://auth0.com/docs/tokens/json-web-tokens/json-web-key-set-properties) by passing them as a constructor argument instead (if you want to fetch your keys from a bucket file or a JWKS endpoint for example)
- by default the `RS256` will be used, but you can provide others listed [here](../../src/Security/Key/KeyInterface.php)

As a result, you'll get a [KeyChainInterface](../../src/Security/Key/KeyChainInterface.php) instance:

```php
<?php

use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;

/** @var KeyChainInterface $keyChain */
echo $keyChain->getIdentifier();                   // '1'
echo $keyChain->getPublicKey()->getContent();      // public key content
echo $keyChain->getPublicKey()->getAlgorithm();    // 'RS256'
var_dump($keyChain->getPublicKey()->isFromFile()); // true
```

## Configure a platform

You need to provide configuration for the [platform](http://www.imsglobal.org/spec/lti/v1p3#platforms-and-tools):
- you are representing if you use the library from platform side
- where your tool is deployed on, if you use the library from tool side

For example:
```php
<?php

use OAT\Library\Lti1p3Core\Platform\Platform;

$platform = new Platform(
    'platformIdentifier',                       // [required] identifier
    'platformName',                             // [required] name
    'https://platform.com',                     // [required] audience
    'https://platform.com/oidc-auth',           // [optional] OIDC authentication url
    'https://platform.com/oauth2-access-token'  // [optional] OAuth2 access token url
);
```
**Note**: you can also provide your own implementation of the [PlatformInterface](../../src/Platform/PlatformInterface.php).

## Configure a tool

You need to provide configuration for the [tool](http://www.imsglobal.org/spec/lti/v1p3#platforms-and-tools):
- you are representing if you use the library from tool side
- you want to provide functionality from, if you use the library from platform side

For example:
```php
<?php

use OAT\Library\Lti1p3Core\Tool\Tool;

$tool = new Tool(
    'toolIdentifier',               // [required] identifier
    'toolName',                     // [required] name
    'https://tool.com',             // [required] audience
    'https://tool.com/oidc-init',   // [required] OIDC initiation url
    'https://tool.com/launch',      // [optional] default tool launch url
    'https://tool.com/deep-linking' // [optional] DeepLinking url
);
```
**Note**: you can also provide your own implementation of the [ToolInterface](../../src/Tool/ToolInterface.php).

## Configure a registration

You need then to create a [registration](http://www.imsglobal.org/spec/lti/v1p3#tool-deployment-0), describing how the tool is made available for the platform.

A same platform instance can deploy several tools (or several times the same tool instance), that is why this binding is handled on the deployment ids level.

For example:
```php
<?php

use OAT\Library\Lti1p3Core\Registration\Registration;

$registration = new Registration(
    'registrationIdentifier',  // [required] identifier
    'registrationClientId',    // [required] client id
    $platform,                 // [required] (PlatformInterface) platform 
    $tool,                     // [required] (ToolInterface) tool 
    $deploymentIds,            // [required] (array) deployments ids 
    $platformKeyChain,         // [optional] (KeyChainInterface) key chain of the platform 
    $toolKeyChain,             // [optional] (KeyChainInterface) key chain of the tool 
    $platformJwksUrl,          // [optional] JWKS url of the platform
    $toolJwksUrl,              // [optional] JWKS url of the tool
);
```
**Notes**:
- you can also provide your own implementation of the [RegistrationInterface](../../src/Registration/RegistrationInterface.php)
- depending on the side you act (platform or tool), you need to configure what is relevant regarding the keys and the JWKS urls
- since you should be in control of the way you retrieve your registrations configuration (from YML files, array, database, etc), you have to provide your own implementation of the [RegistrationRepositoryInterface](../../src/Registration/RegistrationRepositoryInterface.php) to fit your needs

## Public key versus JWKS

On a registration creation, you can give both JWKS url and public key for the tool and platform.

To be optimal for signature verification:
- if both JWKS and public key given, the library will always use the public key (to avoid JWKS calls)
- if only one of them given, it'll use the given one
- if none given, it'll throw an error (unable to validate calls)

Using JWKS is recommended (but not mandatory):
- it avoids keys exchange / maintenance processes, and allow easier integrations (keys can rotate, JWKS url remains the same)
- it handles automatically caching (to avoid useless traffic), see possibility to inject a [PSR6 cache](https://www.php-fig.org/psr/psr-6/#cacheitempoolinterface) into the [JwksFetcher](../../src/Security/Jwks/Fetcher/JwksFetcher.php)

Depending on the side you're acting on, you need to provide a `KeyChainInterface` instance that contains a public key and it's associated private key (and passphrase).

For example, if you're acting as a platform:
- the `$platformKeyChain` has to be given, containing public & private keys (to sign platform originating messages)
- either the `$toolKeyChain` (with only a public key) or the `$toolJwksUrl` has to be given to validate tool originating messages

Or, if you're acting as a tool:
- the `$toolKeyChain` has to be given, containing public & private keys (to sign tool originating messages)
- either the `$platformKeyChain` (with only a public key) or the `$platformJwksUrl` has to be given to validate platform originating messages