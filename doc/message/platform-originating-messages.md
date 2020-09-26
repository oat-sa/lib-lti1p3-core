# Platform originating messages

> How to [perform secured platform originating messages](https://www.imsglobal.org/spec/security/v1p0/#platform-originating-messages), complying to the [OIDC launch flow](https://www.imsglobal.org/spec/security/v1p0/#openid_connect_launch_flow).

## OIDC flow

Platform originating messages must comply to the [OpenId Connect launch flow](https://www.imsglobal.org/spec/security/v1p0/#openid_connect_launch_flow).

You can find below an OIDC launch flow diagram, with steps numbers:

![OIDC flow](../images/message/platform-to-tool.png)

To handle the OIDC launch flow for platform originating messages, each step will be detailed below, from both platform and tool perspectives.

## Table of contents

- [1 - Platform side: launch generation](#1---platform-side-launch-generation)
- [2 - Tool side: OIDC initiation](#2---tool-side-oidc-initiation)
- [3 - Platform side: OIDC authentication](#3---platform-side-oidc-authentication)
- [4 - Tool side: launch validation](#4---tool-side-launch-validation)

## 1 - Platform side: launch generation

You can find below required steps to generate platform originating messages, needed only if you're acting as a platform.

### Create the LTI resource link

A [LTI resource link](http://www.imsglobal.org/spec/lti/v1p3#resource-link-0) represent a resource made available from a tool to a platform.

First of all, you need to create a [LtiResourceLink](../../src/Resource/LtiResourceLink/LtiResourceLink.php) instance:
```php
<?php

use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLink;

$ltiResourceLink = new LtiResourceLink(
    'resourceLinkIdentifier',
    [
        'url' => 'http://tool.com/resource',
        'title' => 'Some title'
    ]
);
```
**Notes**:
- if no resource link url parameter is given, the launch will be done on the default launch url of the registered tool
- since the platform should be able to manage their resource links the way they want (pre fetched from [deep link](https://www.imsglobal.org/spec/lti-dl/v2p0) for example), you can implement your own [LtiResourceLinkInterface](../../src/Resource/LtiResourceLink/LtiResourceLinkInterface.php)

### Create an LTI resource link launch request message for the LTI resource link

Once your `LtiResourceLinkInterface` implementation is ready, you need to launch it to a registered tool following the [OIDC launch workflow](https://www.imsglobal.org/spec/security/v1p0#openid_connect_launch_flow), within the context of a registration.

To do so, you can use the [LtiResourceLinkLaunchRequestBuilder](../../src/Message/Launch/Builder/LtiResourceLinkLaunchRequestBuilder.php) to create an LTI resource link launch request, to start OIDC flow:
```php
<?php

use OAT\Library\Lti1p3Core\Message\Launch\Builder\LtiResourceLinkLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;

// Create a builder instance
$builder = new LtiResourceLinkLaunchRequestBuilder();

// Get related registration of the launch
/** @var RegistrationRepositoryInterface $registrationRepository */
$registration = $registrationRepository->find(...);

// Build a launch request
$message = $builder->buildLtiResourceLinkLaunchRequest(
    $ltiResourceLink, // LTI resource link to launch
    $registration,    // related registration
    'loginHint',      // hint about the user login process that will be done on a later step
    null,             // will use the registration default deployment id, but you can pass a specific one
    [
        'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner' // role
    ], 
    [
        new ContextClaim('contextId'),     // LTI claim representing the context 
        'myCustomClaim' => 'myCustomValue' // custom claim
    ]
);
```
**Notes**:
- like the `ContextClaim`, any claim that implement the [MessagePayloadClaimInterface](../../src/Message/Payload/Claim/MessagePayloadClaimInterface.php) will be automatically normalized and added to the message payload claims.
- you can also generate if needed generic platform launches with the [PlatformOriginatingLaunchBuilder](../../src/Message/Launch/Builder/PlatformOriginatingLaunchBuilder.php) 

As a result of the build, you get a [LtiMessageInterface](../../src/Message/LtiMessageInterface.php) instance that can be used in several ways:
```php
<?php

use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;

/** @var LtiMessageInterface $message */

// Main properties you can use as you want to offer the launch to the platform users
echo $message->getUrl();             // url of the launch
echo $message->getParameters();      // parameters of the launch

// Or use those helpers methods to ease the launch interactions
echo $message->toUrl();                // url with launch parameters as query parameters
echo $message->toHtmlLink('click me'); // HTML link, where href is the output url
echo $message->toHtmlRedirectForm();   // HTML hidden form, with possibility of auto redirection
```

All you need to do now is to present this launch request message to the users, to launch them to the tool.

## 2 - Tool side: OIDC initiation

You can find below required steps to handle the initiation an OIDC flow, needed only if you're acting as a tool.

### Handling the OIDC initiation

As a tool, you'll receive an HTTP request containing the [OIDC initiation](https://www.imsglobal.org/spec/security/v1p0#step-2-authentication-request).

You can use the [OidcInitiator](../../src/Security/Oidc/OidcInitiator.php) to handle this:
- it requires a registration repository implementation [as explained here](../quickstart/interfaces.md)
- it expects a [PSR7 ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) to handle
- it will output a [LtiMessageInterface](../../src/Message/LtiMessageInterface.php) instance to be sent back to the platform.

For example:
```php
<?php

use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\OidcInitiator;
use Psr\Http\Message\ServerRequestInterface;

/** @var RegistrationRepositoryInterface $registrationRepository */
$registrationRepository = ...

/** @var ServerRequestInterface $request */
$request = ...

// Create the OIDC initiator
$initiator = new OidcInitiator($registrationRepository);

// Perform the OIDC initiation (including state generation)
$message = $initiator->initiate($request);

// Redirection to the platform
header('Location: ' . $message->toUrl(), true, 302);
die;
```

### OIDC flow initiation redirection automation

This library provides the [OidcInitiationServer](../../src/Security/Oidc/Server/OidcInitiationServer.php) that can be exposed in an application controller to automate a redirect response creation from the [OidcInitiator](../../src/Security/Oidc/OidcInitiator.php) output:
- it expects a [PSR7 ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) to handle
- it will return a [PSR7 ResponseInterface](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface) instance to make the redirection to the platform.

For example:
```php
<?php

use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\OidcInitiator;
use OAT\Library\Lti1p3Core\Security\Oidc\Server\OidcInitiationServer;
use Psr\Http\Message\ServerRequestInterface;

/** @var RegistrationRepositoryInterface $registrationRepository */
$registrationRepository = ...

/** @var ServerRequestInterface $request */
$request = ...

// Create the OIDC server
$server = new OidcInitiationServer(new OidcInitiator($registrationRepository));

// Redirect response from OIDC initiation (including state generation, via 302)
$response = $server->handle($request);
```

## 3 - Platform side: OIDC authentication

You can find below required steps to provide authentication during the OIDC flow and performing the launch, needed only if you're acting as a platform.

### Perform the OIDC authentication and redirecting to the tool

After the redirection of the tool to the platform, the platform need to provide authentication and redirect to the tool to continue the OIDC flow.

It can be handled with the [OidcAuthenticator](../../src/Security/Oidc/OidcAuthenticator.php):
- it requires a registration repository and a [UserAuthenticatorInterface](../../src/Security/User/UserAuthenticatorInterface.php) implementation [as explained here](../quickstart/interfaces.md)
- it expects a [PSR7 ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) to handle
- it will output a [LtiMessageInterface](../../src/Message/LtiMessageInterface.php) that cab be used to redirect to the tool to finish the OIDC flow.

For example:
```php
<?php

use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\OidcAuthenticator;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use Psr\Http\Message\ServerRequestInterface;

/** @var RegistrationRepositoryInterface $registrationRepository */
$registrationRepository = ...

/** @var UserAuthenticatorInterface $userAuthenticator */
$userAuthenticator = ...

/** @var ServerRequestInterface $request */
$request = ...

// Create the OIDC authenticator
$authenticator = new OidcAuthenticator($registrationRepository, $userAuthenticator);

// Perform the login authentication (delegating to the $userAuthenticator with the hint 'loginHint')
$message = $authenticator->authenticate($request);

// Auto redirection to the tool via the user's browser
echo $message->toHtmlRedirectForm();
```

### OIDC flow authentication redirection automation

This library provides the [OidcAuthenticationServer](../../src/Security/Oidc/Server/OidcAuthenticationServer.php) that can be exposed in an application controller to automate a redirect form response creation from the [OidcAuthenticator](../../src/Security/Oidc/OidcAuthenticator.php) output:
- it expects a [PSR7 ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) to handle
- it will return a [PSR7 ResponseInterface](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface) instance to make the redirection to the tool via a form POST.

For example:
```php
<?php

use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\OidcAuthenticator;
use OAT\Library\Lti1p3Core\Security\Oidc\Server\OidcAuthenticationServer;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use Psr\Http\Message\ServerRequestInterface;

/** @var RegistrationRepositoryInterface $registrationRepository */
$registrationRepository = ...

/** @var UserAuthenticatorInterface $userAuthenticator */
$userAuthenticator = ...

/** @var ServerRequestInterface $request */
$request = ...

// Create the OIDC server
$server = new OidcAuthenticationServer(new OidcAuthenticator($registrationRepository));

// Redirect response from OIDC authentication (via form POST)
$response = $server->handle($request);
```

## 4 - Tool side: launch validation

You can find below required steps to validate a platform originating message, needed only if you're acting as a tool.

### Validate the launch after OIDC flow

As a tool, you'll receive an HTTP request containing the [LTI resource link launch request message](http://www.imsglobal.org/spec/lti/v1p3#resource-link-launch-request-message).

The [ToolLaunchValidator](../../src/Message/Launch/Validator/ToolLaunchValidator.php) can be used for this:
- it requires a registration repository and a nonce repository implementations [as explained here](../quickstart/interfaces.md)
- it expects a [PSR7 ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) to validate
- it will output a [LtiResourceLinkLaunchRequestValidationResult](../../src/Message/Launch/Validator/LaunchRequestValidationResult.php) representing the launch validation, the related registration and the message payload itself.

For example:
```php
<?php

use OAT\Library\Lti1p3Core\Message\Launch\Validator\ToolLaunchValidator;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;

/** @var RegistrationRepositoryInterface $registrationRepository */
$registrationRepository = ...

/** @var NonceRepositoryInterface $nonceRepository */
$nonceRepository = ...

/** @var ServerRequestInterface $request */
$request = ...

// Create the validator
$validator = new ToolLaunchValidator($registrationRepository, $nonceRepository);

// Perform validation
$result = $validator->validatePlatformOriginatingLaunch($request);

// Result exploitation
if (!$result->hasError()) {
    // You have access to related registration (to spare queries)
    echo $result->getRegistration()->getIdentifier();

    // And to the LTI message payload (id_token parameter)
    echo $result->getPayload()->getVersion();                 // '1.3.0'
    echo $result->getPayload()->getContext()->getId();        // 'contextId'
    echo $result->getPayload()->getClaim('myCustomClaim');    // 'myCustomValue'
    echo $result->getPayload()->getUserIdentity()->getName(); // given by the platform during OIDC authentication step
    
    // If needed, you can also access the OIDC state (state parameter)
    echo $result->getState()->getToken()->__toString();    // state JWT
    echo $result->getState()->getToken()->getClaim('jti'); // state JWT id

    // If needed, you can also access the validation successes
    foreach ($result->getSuccesses() as $success) {
        echo $success;
    }
} 
```
