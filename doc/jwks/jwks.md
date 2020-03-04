# JWKS endpoint service

> Documentation about how to expose a [JWKS endpoint](https://auth0.com/docs/tokens/concepts/jwks) (JSON Web Key Set) easily from this library, recommended as a Platform.

## Exporting a JWK from a key chain

Considering you have for example on your side this key pair:
- public key path: /home/user/.ssh/id_rsa.pub
- private key path: /home/user/.ssh/id_rsa
- private key passphrase: test

To extract the JWK properties, you can use the [JwkExporter](../../src/Security/Jwks/JwkExporter.php) as following:

```php
<?php

use OAT\Library\Lti1p3Core\Security\Jwks\JwkExporter;
use OAT\Library\Lti1p3Core\Security\Key\KeyChain;

$keyChain = new KeyChain(
    '1',
    'mySetName',
    'file://home/user/.ssh/id_rsa.pub',
    'file://home/user/.ssh/id_rsa',
    'test'
);

$jwkExport = (new JwkExporter())->export($keyChain);
```

**Note**: `$jwkExport` contains the needed [JWK properties](https://auth0.com/docs/tokens/references/jwks-properties):

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

## Exposing a JWKS form several key chains

Considering you have for example on your side those key pairs:

Pair 1:
- public key path: /home/user/.ssh/pair1/id_rsa.pub
- private key path: /home/user/.ssh/pair1/id_rsa

Pair 2:
- public key path: ~/.ssh/pair2/id_rsa.pub
- private key path: ~/.ssh/pair2/id_rsa

And considering you provided you own [KeyChainRepositoryInterface](../../src/Security/Key/KeyChainRepositoryInterface.php) implementation:

```php
<?php

use OAT\Library\Lti1p3Core\Security\Key\KeyChain;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepositoryInterface;

$keyChain1 = new KeyChain(
    '1',
    'mySetName',
    'file://home/user/.ssh/pair1/id_rsa.pub',
    'file://home/user/.ssh/pair1/id_rsa',
    'test'
);

$keyChain2 = new KeyChain(
    '2',
    'mySetName',
    'file://home/user/.ssh/pair2/id_rsa.pub',
    'file://home/user/.ssh/pair2/id_rsa',
    'test'
);

/** @var KeyChainRepositoryInterface $repository */
$keySets = $repository->findBySetName('mySetName'); // same as [$keyChain1, $keyChain2]
```

To extract the JWKS properties, you can use the [JwksExporter](../../src/Security/Jwks/JwksExporter.php) as following:

```php
<?php

use OAT\Library\Lti1p3Core\Security\Jwks\JwkExporter;
use OAT\Library\Lti1p3Core\Security\Jwks\JwksExporter;
use OAT\Library\Lti1p3Core\Security\Key\KeyChain;

$keyChain = new KeyChain(
    '1',
    'mySetName',
    'file://home/user/.ssh/id_rsa.pub',
    'file://home/user/.ssh/id_rsa',
    'test'
);

$jwksExport = (new JwksExporter($repository, new JwkExporter()))->export('mySetName');
```

**Note**: `$jwksExport` contains the needed [JWKS properties](https://auth0.com/docs/tokens/references/jwks-properties):

```json
{
"keys": [
    {
        "alg": "RS256",
        "kty": "RSA",
        "use": "sig",
        "n": "...",
        "e": "...",
        "kid": "1"
    },
    {
        "alg": "RS256",
        "kty": "RSA",
        "use": "sig",
        "n": "...",
        "e": "...",
        "kid": "2"
    }
]}
```