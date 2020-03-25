# LTI 1.3 Core Library

> PHP library for [LTI 1.3 Core](http://www.imsglobal.org/spec/lti/v1p3) implementations as platforms and / or as tools.

## Table of contents

- [Specifications](#specifications)
- [Installation](#installation)
- [Concepts](#concepts)
- [Tutorials](#tutorials)
- [Tests](#tests)

## Specifications

- [IMS LTI 1.3 Core](http://www.imsglobal.org/spec/lti/v1p3)
- [IMS Security](https://www.imsglobal.org/spec/security/v1p0)

## Installation

```console
$ composer require oat-sa/lib-lti1p3-core
```

## Concepts

Extraction of the main concepts of the [LTI 1.3 Core](http://www.imsglobal.org/spec/lti/v1p3) specification:

###  Platforms and tools

- [Platform](src/Platform/PlatformInterface.php): any kind of platform that needs to delegate bits of functionality out to a suite of tools.
- [Tool](src/Tool/ToolInterface.php): external application or service providing functionality to a platform.
- [Deployment](src/Deployment/DeploymentInterface.php): defines the scope of contexts under which a tool is made available for a platform.

### Messages

Messages represent integration between platforms and tools intermediated by a user's browser.

- [Lti Link](src/Link/LinkInterface.php): reference to a specific tool feature or resource, presented by a platform.
- [Lti Launch Request](src/Launch/LaunchRequestInterface.php): refers to the process in which a user interacts with an LTI Link within the platform and is subsequently "launched" into a tool.
- [Lti Message](src/Message/LtiMessageInterface.php): represents the data exchanged between a platform and a tool during an LTI Launch.

### Services

Services represent direct connections between platforms and tools.

- to be done

## Tutorials

Some tutorials, presented by topics.

### Resource Link
- how to use the [3rd party login initiation creation service](doc/platform/third_party_initiated_login_creation.md)

Tool
- how to provide the [3rd party login initiation handling endpoint](doc/tool/third_party_initiated_login_handling.md)

### Security
- how to provide a [JWKS endpoint](doc/security/jwks.md)

## Tests

To run tests:

```console
$ vendor/bin/phpunit
```
**Note**: see [phpunit.xml.dist](phpunit.xml.dist) for available test suites.