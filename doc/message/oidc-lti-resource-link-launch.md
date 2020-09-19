# LTI resource link launch process with OIDC

> How to perform a [LTI launch with OIDC](https://www.imsglobal.org/spec/security/v1p0#openid_connect_launch_flow) for a [LTI resource link](http://www.imsglobal.org/spec/lti/v1p3#resource-link-0).

## Table of contents

- [Platform side: LTI resource link launch request generation](#platform-side-lti-resource-link-launch-request-generation)
- [Tool side: OIDC flow initiation](#tool-side-oidc-flow-initiation)
- [Platform side: OIDC flow authentication](#platform-side-oidc-flow-authentication)
- [Tool side: validating the launch after OIDC flow](#tool-side-validating-the-launch-after-oidc-flow)

## Platform side: LTI resource link launch request generation

You can find below required steps to generate an LTI resource link launch request (with OIC), needed only if you're acting as a platform.

### Create the LTI resource link

A [LTI resource link](http://www.imsglobal.org/spec/lti/v1p3#resource-link-0) represent a resource made available from a tool to a platform.

First of all, you need to create a [LtiResourceLink](../../src/Message/Resource/LtiResourceLink.php) instance:
```php
<?php

use OAT\Library\Lti1p3Core\Message\Resource\LtiResourceLink;

$ltiResourceLink = new LtiResourceLink(
    'resourceLinkIdentifier',   // [required] identifier
    'http://tool.com/resource', // [optional] url of the resource on the tool
    'title',                    // [optional] title
    'description'               // [optional] description
);
```
**Notes**:
- if no resource link url is given, the launch will be done on the default launch url of the registered tool
- since the platform should be able to retrieve resource links from their storage (pre fetched from [deep link](https://www.imsglobal.org/spec/lti-dl/v2p0) for example), you can implement your own [LtiResourceLinkInterface](../../src/Message/Resource/LtiResourceLinkInterface.php)

### Create an LTI resource link launch request (OIDC) for the LTI resource link

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
$launchRequest = $builder->build(
    $ltiResourceLink,
    $registration,
    'loginHint', // hint about the user login process that will be done on a later step
    null,        // will use the registration default deployment id, but you can pass a specific one
    [
        'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner' // role
    ], 
    [
        new ContextClaim('contextId'),     // LTI claim representing the context 
        'myCustomClaim' => 'myCustomValue' // custom claim
    ]
);
```
**Note**: like the `ContextClaim` class, any claim that implement the [MessagePayloadClaimInterface](../../src/Message/Payload/Claim/MessagePayloadClaimInterface.php) will be automatically normalized and added to the message payload claims.

As a result of the build, you get a [LtiMessageInterface](../../src/Message/LtiMessageInterface.php) instance that can be used in several ways:
```php
<?php

use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;

/** @var LtiMessageInterface $launchRequest */

// Main properties you can use as you want to offer the launch to the platform users
echo $launchRequest->getUrl();             // url of the launch
echo $launchRequest->getParameters();      // parameters of the launch

// Or use those helpers methods to ease the launch interactions
echo $launchRequest->toUrl();                // url with launch parameters as query parameters
echo $launchRequest->toHtmlLink('click me'); // HTML link, where href is the output url
echo $launchRequest->toHtmlRedirectForm();   // HTML hidden form, with possibility of auto redirection
```

All you need to do now is to present this launch request to the users, to launch them to the tool.

## Tool side: OIDC flow initiation

You can find below required steps to handle the initiation an OIDC flow, needed only if you're acting as a tool.

### Handling the OIDC flow initiation

As a tool, you'll receive an HTTP request containing the [OIDC flow initiation](https://www.imsglobal.org/spec/security/v1p0#step-2-authentication-request).

You can use the [OidcInitiator](../../src/Security/Oidc/OidcInitiator.php) to handle this:
- it requires a registration repository implementation [as explained here](../quickstart/interfaces.md)
- it expects a [PSR7 ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) to handle
- and it will output a [LtiMessageInterface](../../src/Message/LtiMessageInterface.php) instance to be sent back to the platform.

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
$oidcAuthenticationRequest = $initiator->initiate($request);

// Redirection to the platform
header('Location: ' . $oidcAuthenticationRequest->toUrl(), true, 302);
die;
```

### OIDC flow initiation redirection automation

This library provides the [OidcInitiationServer](../../src/Security/Oidc/Server/OidcInitiationServer.php) that can be exposed in an application controller to automate a redirect response creation from the [OidcInitiator](../../src/Security/Oidc/OidcInitiator.php) output:
- it expects a [PSR7 ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) to handle
- and it will return a [PSR7 ResponseInterface](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface) instance to make the redirection to the platform.

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

## Platform side: OIDC flow authentication

You can find below required steps to provide authentication during the OIDC flow and performing a launch, needed only if you're acting as a platform.

### Perform the OIDC authentication and launching the tool

After the redirection of the tool to the platform, the platform need to provide authentication and redirect to the tool to continue the OIDC flow.

It can be handled with the [OidcAuthenticator](../../src/Security/Oidc/OidcAuthenticator.php):
- it requires a registration repository and a [UserAuthenticatorInterface](../../src/Security/User/UserAuthenticatorInterface.php) implementation [as explained here](../quickstart/interfaces.md)
- it expects a [PSR7 ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) to handle
- and it will output a [LtiMessageInterface](../../src/Message/LtiMessageInterface.php) that cab be used to redirect to the tool to finish the OIDC flow.

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
$launchRequest = $authenticator->authenticate($request);

// Auto redirection to the tool via the  user's browser
echo $launchRequest->toHtmlRedirectForm();
```

### OIDC flow authentication redirection automation

This library provides the [OidcAuthenticationServer](../../src/Security/Oidc/Server/OidcAuthenticationServer.php) that can be exposed in an application controller to automate a redirect form response creation from the [OidcAuthenticator](../../src/Security/Oidc/OidcAuthenticator.php) output:
- it expects a [PSR7 ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) to handle
- and it will return a [PSR7 ResponseInterface](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface) instance to make the redirection to the tool via a form POST.

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

## Tool side: validating the launch after OIDC flow

You can find below required steps to validate an LTI launch request, needed only if you're acting as a tool.

### Validate the launch request after OIDC flow

As a tool, you'll receive an HTTP request containing the [LTI resource link launch request](http://www.imsglobal.org/spec/lti/v1p3#resource-link-launch-request-message).

The [LtiResourceLinkLaunchRequestValidator](../../src/Message/Launch/Validator/LtiResourceLinkLaunchRequestValidator.php) can be used for this:
- it requires a registration repository and a nonce repository implementations [as explained here](../quickstart/interfaces.md)
- it expects a [PSR7 ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) to validate
- it will output a [LtiResourceLinkLaunchRequestValidationResult](../../src/Message/Launch/Validator/LtiResourceLinkLaunchRequestValidationResult.php) representing the launch validation, the related registration and the message payload itself.

For example:
```php
<?php

use OAT\Library\Lti1p3Core\Message\Launch\Validator\LtiResourceLinkLaunchRequestValidator;
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
$validator = new LtiResourceLinkLaunchRequestValidator($registrationRepository, $nonceRepository);

// Perform validation
$result = $validator->validate($request);

// Result exploitation
if (!$result->hasError()) {
    // You have access to related registration (to spare queries)
    echo $result->getRegistration()->getIdentifier();

    // And to the LTI message payload
    echo $result->getPayload()->getVersion();                 // '1.3.0'
    echo $result->getPayload()->getContext()->getId();        // 'contextId'
    echo $result->getPayload()->getClaim('myCustomClaim');    // 'myCustomValue'
    echo $result->getPayload()->getUserIdentity()->getName(); // given by the platform during OIDC authentication step
    
    // If needed, you can also access the OIDC state
    echo $result->getState()->getToken()->__toString();    // state JWT
    echo $result->getState()->getToken()->getClaim('jti'); // state JWT id

    // If needed, you can also access the validation successes
    foreach ($result->getSuccesses() as $success) {
        echo $success;
    }
} 
```
