# LTI 1.3 Core Library

[![Latest Version](https://img.shields.io/github/tag/oat-sa/lib-lti1p3-core.svg?style=flat&label=release)](https://github.com/oat-sa/lib-lti1p3-core/tags)
[![License GPL2](http://img.shields.io/badge/licence-GPL%202.0-blue.svg)](http://www.gnu.org/licenses/gpl-2.0.html)
[![Build Status](https://github.com/oat-sa/lib-lti1p3-core/actions/workflows/build.yaml/badge.svg?branch=master)](https://github.com/oat-sa/lib-lti1p3-core/actions)
[![Tests Coverage Status](https://coveralls.io/repos/github/oat-sa/lib-lti1p3-core/badge.svg?branch=master)](https://coveralls.io/github/oat-sa/lib-lti1p3-core?branch=master)
[![Psalm Level Status](https://shepherd.dev/github/oat-sa/lib-lti1p3-core/level.svg)](https://shepherd.dev/github/oat-sa/lib-lti1p3-core)
[![Packagist Downloads](http://img.shields.io/packagist/dt/oat-sa/lib-lti1p3-core.svg)](https://packagist.org/packages/oat-sa/lib-lti1p3-core)

> PHP library for [LTI 1.3 Core](http://www.imsglobal.org/spec/lti/v1p3) implementations as platforms and / or as tools.

## Table of contents

- [Specifications](#specifications)
- [Installation](#installation)
- [Concepts](#concepts)
- [Tutorials](#tutorials)
- [Wiki](#wiki)
- [Tests](#tests)

## Specifications

- [IMS LTI 1.3 Core](http://www.imsglobal.org/spec/lti/v1p3)
- [IMS Security](https://www.imsglobal.org/spec/security/v1p0)

## Installation

```console
$ composer require oat-sa/lib-lti1p3-core
```

## Concepts

You can find below the implementations of the main concepts of the [LTI 1.3 Core](http://www.imsglobal.org/spec/lti/v1p3) specification.

###  Platforms, tools and registrations

- [Platform](src/Platform/PlatformInterface.php): any kind of platform that needs to delegate bits of functionality out to a suite of tools.
- [Tool](src/Tool/ToolInterface.php): external application or service providing functionality to a platform.
- [Registration](src/Registration/RegistrationInterface.php): defines the scope of contexts under which a tool is made available for a platform.

### Messages

Messages represent integration between platforms and tools intermediated by a user's browser.

- [LTI Message](src/Message/LtiMessageInterface.php): reference to an exchange between platforms and tools in message based communications.
- [LTI Message Payload](src/Message/Payload/LtiMessagePayloadInterface.php): reference to the payload (JWT) of an exchange between platforms and tools in message based communications, containing LTI claims.
- [LTI Resource Link](src/Resource/LtiResourceLink/LtiResourceLinkInterface.php): reference to a resource made available from a tool for a platform.
- [LTI Resource Link Launch](src/Message/Launch/Builder/LtiResourceLinkLaunchRequestBuilder.php): refers to the process in which a user interacts with an LTI Resource Link within the platform and is subsequently "launched" into a tool.

### Services

Services represent direct connections between platforms and tools (without involving user's browser).

- [Server](src/Service/Server): server side of LTI service
- [Client](src/Service/Client): client side of LTI service

## Tutorials

You can find below some tutorials, presented by topics.

### Quick start

- how to [configure the core library](doc/quickstart/configuration.md)
- how to [implement the core library interfaces](doc/quickstart/interfaces.md)
- how to [expose a JWKS endpoint](doc/quickstart/jwks.md)

### Messages interactions

- how to [handle platform originating LTI messages](doc/message/platform-originating-messages.md)
- how to [handle tool originating LTI messages](doc/message/tool-originating-messages.md)

### Services interactions

- how to [set up an LTI service server](doc/service/service-server.md)
- how to [use the LTI service client](doc/service/service-client.md)

## Wiki

You can find more information in the [library wiki](https://github.com/oat-sa/lib-lti1p3-core/wiki). 

## Tests

To run tests:

```console
$ vendor/bin/phpunit
```
**Note**: see [phpunit.xml.dist](phpunit.xml.dist) for available test suites.