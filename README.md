# LTI1P3Core library

> PHP library for LTI 1.3 management

## Table of contents
- [Installation](#installation)
- [Documentation](#documentation)
- [Tests](#tests)

## Installation

```console
$ composer require oat-sa/lib-lti1p3-core
```

## Documentation

You can find documentation on following topics:

Platform
- how to provide the [3rd party login initiation creation endpoint](doc/platform/third_party_initiated_login_creation.md)

Tool
- how to provide the [3rd party login initiation handling endpoint](doc/tool/third_party_initiated_login_handling.md)

Security
- how to provide a [JWKS endpoint](doc/security/jwks.md)

## Tests

To run tests:

```console
$ vendor/bin/phpunit
```
**Note**: see [phpunit.xml.dist](phpunit.xml.dist) for available test suites.