# JWKS endpoint service

> Documentation about how to expose a [JWKS endpoint](https://auth0.com/docs/tokens/concepts/jwks) (JSON Web Key Set) with this library.

## Exporting a JWK from a key chain (RS SHA 256)

Considering you have for example on your side this key pair:
- public key path: `/home/user/.ssh/id_rsa.pub`
- private key path: `/home/user/.ssh/id_rsa`
- private key passphrase: `test`

To extract the JWK properties, you can use the [JwkRS256Exporter](../../src/Security/Jwks/Exporter/Jwk/JwkRS256Exporter.php) as following:

```php
<?php

use OAT\Library\Lti1p3Core\Security\Jwks\Exporter\Jwk\JwkRS256Exporter;
use OAT\Library\Lti1p3Core\Security\Key\KeyChain;

$keyChain = new KeyChain(
    '1',
    'mySetName',
    'file://home/user/.ssh/id_rsa.pub',
    'file://home/user/.ssh/id_rsa',
    'test'
);

$jwkExport = (new JwkRS256Exporter())->export($keyChain);
```

**Notes**:
- the variable `$jwkExport` will contain the needed [JWK properties](https://auth0.com/docs/tokens/references/jwks-properties):
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
- If you want to support other algorithms than RS SHA 256, you can  implement the [JwkExporterInterface](../../src/Security/Jwks/Exporter/Jwk/JwkExporterInterface.php)

## Create a JWKS endpoint from several key chains

Considering you have for example on your side those key pairs:

Pair 1:
- public key path: `/home/user/.ssh/pair1/id_rsa.pub`
- private key path: `/home/user/.ssh/pair1/id_rsa`

Pair 2:
- public key path: `/home/user/.ssh/pair2/id_rsa.pub`
- private key path: `/home/user/.ssh/pair2/id_rsa`

You can then use the [KeyChainRepository](../../src/Security/Key/KeyChainRepository.php):

```php
<?php

use OAT\Library\Lti1p3Core\Security\Key\KeyChain;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepository;

$keyChain1 = new KeyChain(
    'kid1',
    'myKeySetName',
    'file://home/user/.ssh/pair1/id_rsa.pub',
    'file://home/user/.ssh/pair1/id_rsa',
    'test'
);

$keyChain2 = new KeyChain(
    'kid2',
    'myKeySetName',
    'file://home/user/.ssh/pair2/id_rsa.pub',
    'file://home/user/.ssh/pair2/id_rsa',
    'test'
);

$keyChainRepository = new KeyChainRepository();
$keyChainRepository
    ->addKeyChain($keyChain1)
    ->addKeyChain($keyChain2);

$keySet = $keyChainRepository->findByKeySetName('myKeySetName'); //  = [$keyChain1, $keyChain2]
```
**Note**: you can also provide your own [KeyChainRepositoryInterface](../../src/Security/Key/KeyChainRepositoryInterface.php) implementation, to store keys in database by example.

To extract the JWKS properties, you can use the [JwksExporter](../../src/Security/Jwks/Exporter/JwksExporter.php) as following:

```php
<?php

use OAT\Library\Lti1p3Core\Security\Jwks\Exporter\JwksExporter;

$jwksExport = (new JwksExporter($keyChainRepository))->export('myKeySetName');
```

**Note**: `$jwksExport` contains the needed [JWKS properties](https://auth0.com/docs/tokens/references/jwks-properties) ready to be exposed from an HTTP JSON response:

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