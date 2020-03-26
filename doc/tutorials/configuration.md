# Library configuration

> How to provide configuration step by step, to be able to use the library as a tool, or a platform, or both.

## Configure keys

Whether you act as platform, tool or both, you need to provide security keys to be able to sign the messages that will be exchanged during LTI interactions.

Considering you have for example on your side this key chain:
- public key path: `/home/user/.ssh/id_rsa.pub`
- private key path: `/home/user/.ssh/id_rsa`
- private key passphrase: `test`

You can then provide the following [key chain](../../src/Security/Key/KeyChainInterface.php):
```php
<?php

use OAT\Library\Lti1p3Core\Security\Key\KeyChain;

$keyChain = new KeyChain(
    '1',                                // [required] identifier (for kid)
    'mySetName',                        // [required] key set name (for grouping)
    'file://home/user/.ssh/id_rsa.pub', // [required] public key (file or content)
    'file://home/user/.ssh/id_rsa',     // [optional] private key (file or content)
    'test'                              // [optional] private key passphrase
);
```
**Note**: given example deals with local key files, automatically done when prefixed by `file://`. You can provide the public / private key contents by passing them as a constructor argument instead (if you want to fetch your keys from a bucket or a database by example).

## Configure a platform

You need to provide configuration for the [platform](http://www.imsglobal.org/spec/lti/v1p3#platforms-and-tools):
- you are representing if you use the library from platform side
- where your tool is deployed on, if you use the library from tool side

By example:
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

By example:
```php
<?php

use OAT\Library\Lti1p3Core\Tool\Tool;

$tool = new Tool(
    'toolIdentifier',                   // [required] identifier
    'toolName',                         // [required] name
    'https://platform.com/oidc-init',   // [optional] OIDC login initiation url
    'https://platform.com/launch',      // [optional] LTI default ResourceLink launch url
    'https://platform.com/deep-launch'  // [optional] LTI DeepLink launch url
);
```
**Note**: you can also provide your own implementation of the [ToolInterface](../../src/Tool/ToolInterface.php).

## Configure a deployment

You need then to create a [deployment](http://www.imsglobal.org/spec/lti/v1p3#tool-deployment-0), referencing how the tool is deployed for the platform.

A same platform instance can deploy several tools (or several times the same tool instance), that is why this bind is handled on deployment level.

By example:
```php
<?php

use OAT\Library\Lti1p3Core\Deployment\Deployment;

$deployment = new Deployment(
    'deploymentIdentifier',  // [required] identifier
    'deploymentClientId',    // [required] client id
    $platform,               // [required] (PlatformInterface) platform 
    $tool,                   // [required] (ToolInterface) tool 
    $platformKeyChain,       // [optional] (KeyChainInterface) key chain of the platform 
    $toolKeyChain,           // [optional] (KeyChainInterface) key chain of the tool 
    $platformJwksUrl,        // [optional] JWKS url of the platform
    $toolJwksUrl,            // [optional] JWKS url of the tool
);
```
**Notes**:
- you can also provide your own implementation of the [DeploymentInterface](../../src/Deployment/DeploymentInterface.php),
- depending on the side you act (platform or tool), you need to configure what is relevant regarding the keys and the JWKS urls,
- for signature verification, the library will try first to use first the configured key chain if given, and fallback on a JWKS call to avoid performances issues,
- since you should be in control of the way you give your configuration (from YML files, array, database, etc), you have to provide your own implementation of the [DeploymentRepositoryInterface](../../src/Deployment/DeploymentRepositoryInterface.php).