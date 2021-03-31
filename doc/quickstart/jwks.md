# JWKS endpoint

> How to expose a [JWKS endpoint](https://auth0.com/docs/tokens/concepts/jwks) (JSON Web Key Set) with this library.

**Note**: The algorithm `RS256` is used by default.

## Table of contents

- [Export a JWK from a key chain](#export-a-jwk-from-a-key-chain)
- [Export a JWKS from multiple key chains](#export-a-jwks-from-multiple-key-chains)
- [Provide a JWKS response](#provide-a-jwks-response)

## Export a JWK from a key chain

Considering you have for example on your side this key chain:
- public key path: `/home/user/.ssh/id_rsa.pub`
- private key path: `/home/user/.ssh/id_rsa`
- private key passphrase: `test`

To extract the JWK (JSON Web Key) properties, you can use the [JwkRS256Exporter](../../src/Security/Jwks/Exporter/Jwk/JwkRS256Exporter.php) as following:

```php
<?php

use OAT\Library\Lti1p3Core\Security\Jwks\Exporter\Jwk\JwkRS256Exporter;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainFactory;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;

$keyChain = (new KeyChainFactory)->create(
    '1',
    'mySetName',
    'file://home/user/.ssh/id_rsa.pub',
    'file://home/user/.ssh/id_rsa',
    'test',
     KeyInterface::ALG_RS256
);

$jwkExport = (new JwkRS256Exporter())->export($keyChain);
```

**Notes**:
- the `$jwkExport` variable now contain the needed [JWK properties](https://auth0.com/docs/tokens/references/jwks-properties):
    ```json
    {
        "alg": "RS256",
        "kty": "RSA",
        "use": "sig",
        "n": "...",
        "e": "...",
        "kid": "1"
    }
    ```
- If you want to support other algorithms than RS256, you can implement the [JwkExporterInterface](../../src/Security/Jwks/Exporter/Jwk/JwkExporterInterface.php).

## Export a JWKS from multiple key chains

Considering you have for example on your side those key chains:

Chain 1:
- public key path: `/home/user/.ssh/chain1/id_rsa.pub`
- private key path: `/home/user/.ssh/chain1/id_rsa`
- private key passphrase: `test1`

Chain 2:
- public key path: `/home/user/.ssh/chain2/id_rsa.pub`
- private key path: `/home/user/.ssh/chain2/id_rsa`
- private key passphrase: `test2`

You can then use the [KeyChainRepository](../../src/Security/Key/KeyChainRepository.php):

```php
<?php

use OAT\Library\Lti1p3Core\Security\Key\KeyChainFactory;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepository;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;

$keyChain1 = (new KeyChainFactory)->create(
    'kid1',
    'myKeySetName',
    'file://home/user/.ssh/chain1/id_rsa.pub',
    'file://home/user/.ssh/chain1/id_rsa',
    'test1',
     KeyInterface::ALG_RS256
);

$keyChain2 = (new KeyChainFactory)->create(
    'kid2',
    'myKeySetName',
    'file://home/user/.ssh/chain2/id_rsa.pub',
    'file://home/user/.ssh/chain2/id_rsa',
    'test2',
     KeyInterface::ALG_RS256
);

$keyChainRepository = new KeyChainRepository();
$keyChainRepository
    ->addKeyChain($keyChain1)
    ->addKeyChain($keyChain2);

$keySet = $keyChainRepository->findByKeySetName('myKeySetName'); //  = [$keyChain1, $keyChain2]
```

**Note**: you can also provide your own [KeyChainRepositoryInterface](../../src/Security/Key/KeyChainRepositoryInterface.php) implementation, to store keys in database by example.

To extract the JWKS (JSON Web Key Set) properties for you key set name `myKeySetName`, you can use the [JwksExporter](../../src/Security/Jwks/Exporter/JwksExporter.php) as following:

```php
<?php

use OAT\Library\Lti1p3Core\Security\Jwks\Exporter\JwksExporter;

$jwksExport = (new JwksExporter($keyChainRepository))->export('myKeySetName');
```

Now the `$jwksExport` array contains the needed [JWKS properties](https://auth0.com/docs/tokens/references/jwks-properties) ready to be exposed to provide a JWKS endpoint from your application:

```json
{
    "keys": [
        {
            "alg": "RS256",
            "kty": "RSA",
            "use": "sig",
            "n": "...",
            "e": "...",
            "kid": "kid1"
        },
        {
            "alg": "RS256",
            "kty": "RSA",
            "use": "sig",
            "n": "...",
            "e": "...",
            "kid": "kid2"
        }
    ]
}
```

## Provide a JWKS response

You can expose the [JwksRequestHandler](../../src/Security/Jwks/Server/JwksRequestHandler.php) in an application controller to provide a ready to use JWKS [PSR7 response](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface) for a given key set name:

```php
<?php

use OAT\Library\Lti1p3Core\Security\Jwks\Exporter\JwksExporter;
use OAT\Library\Lti1p3Core\Security\Jwks\Server\JwksRequestHandler;

$handler = new JwksRequestHandler(new JwksExporter($keyChainRepository));

$response = $handler->handle('myKeySetName');
```

**Note**: Up to you to provide the logic to retrieve the key set name to expose.
