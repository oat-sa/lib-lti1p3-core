# LTI service client

> How to use the [ServiceClient](../../src/Service/Client/ServiceClient.php) to perform authenticated LTI service calls.

## Table of contents

- [Features](#features)
- [Usage](#usage)

## Features

You may need to perform [authenticated service calls](https://www.imsglobal.org/spec/security/v1p0#securing_web_services) from your tool to a platform (ex: LTI Advantage Services)

To do so, you can use the [ServiceClient](../../src/Service/Client/ServiceClient.php) that permits:
- to call platform endpoints, returning a [PSR7 response](https://www.php-fig.org/psr/psr-7)
- to perform automatically the required [OAuth2 authentication](https://www.imsglobal.org/spec/security/v1p0#using-json-web-tokens-with-oauth-2-0-client-credentials-grant) to get an access token
- to handle automatically the access token caching if you provide an optional [PSR6 cache](https://www.php-fig.org/psr/psr-6/#cacheitempoolinterface) instance

## Usage

To use it, you can simply do by example:
```php
<?php

use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Service\Client\ServiceClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Cache\CacheItemPoolInterface;

// Get related registration of the launch
/** @var RegistrationRepositoryInterface $registrationRepository */
$registration = $registrationRepository->find(...);

// Optional but HIGHLY RECOMMENDED cache for access tokens
/** @var CacheItemPoolInterface $cache */
$cache = ...

$client = new ServiceClient($cache);

/** @var ResponseInterface $response */
$response = $client->request($registration, 'GET', 'https://platform.com/some-service-url', [...]);
```
**Note**: the client decorates by default a [guzzle](http://docs.guzzlephp.org/en/stable/) client, but you can provide your own by implementing [ServiceClientInterface](../../src/Service/Client/ServiceClientInterface.php)
