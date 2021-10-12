# Platform originating LTI messages

> How to [perform secured platform originating LTI messages launches](https://www.imsglobal.org/spec/security/v1p0/#platform-originating-messages) (platform -> tool), complying to the [OIDC launch flow](https://www.imsglobal.org/spec/security/v1p0/#openid_connect_launch_flow).

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

You can find below required steps to generate a platform originating message, needed only if you're acting as a platform.

### Create the message

As a platform, you can create a platform originating message for a tool within the context of a registration.

To do so, you can use the [PlatformOriginatingLaunchBuilder](../../src/Message/Launch/Builder/PlatformOriginatingLaunchBuilder.php):
```php
<?php

use OAT\Library\Lti1p3Core\Message\Launch\Builder\PlatformOriginatingLaunchBuilder;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;

// Create a builder instance
$builder = new PlatformOriginatingLaunchBuilder();

// Get related registration of the launch
/** @var RegistrationRepositoryInterface $registrationRepository */
$registration = $registrationRepository->find(...);

// Build a launch message
$message = $builder->buildPlatformOriginatingLaunch(
    $registration,                                               // related registration
    LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST, // message type of the launch, as an example: 'LtiDeepLinkingResponse'
    'http://tool.com/launch',                                    // target link uri of the launch (final destination after OIDC flow)
    'loginHint',                                                 // login hint that will be used afterwards by the platform to perform authentication
    null,                                                        // will use the registration default deployment id, but you can pass a specific one
    [
        'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner' // role
    ],
    [
        'myCustomClaim' => 'myCustomValue',    // custom claim
        new ContextClaim('contextIdentifier')  // LTI claim representing the context of the launch 
    ]
);
```
**Note**: like the `ContextClaim` class, any claim that implement the [MessagePayloadClaimInterface](../../src/Message/Payload/Claim/MessagePayloadClaimInterface.php) will be automatically normalized and added to the message payload claims.

### Launch the message

As a result of the build, you get a [LtiMessageInterface](../../src/Message/LtiMessageInterface.php) instance that has to be used in the following ways:

```php
<?php

use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;

/** @var LtiMessageInterface $message */

// Main message properties you can use as you want to offer the launch to the platform users
echo $message->getUrl();               // url of the launch
echo $message->getParameters()->all(); // array of parameters of the launch

// Or use those helpers methods to ease the launch interactions
echo $message->toUrl();                // url with launch parameters as query parameters
echo $message->toHtmlLink('click me'); // HTML link, where href is the output url
echo $message->toHtmlRedirectForm();   // HTML hidden form, with possibility of auto redirection
```

### Launching an LTI Resource Link

This library also allow you to perform easily launches of an [LTI Resource Link](http://www.imsglobal.org/spec/lti/v1p3/#resource-link-launch-request-message).

This becomes handy when a platform owns an LTI Resource Link to a tool resource (previously fetched with [DeepLinking](https://www.imsglobal.org/spec/lti-dl/v2p0) for example).

First of all, you need to create or retrieve an [LtiResourceLink](../../src/Resource/LtiResourceLink/LtiResourceLink.php) instance:
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

Once your `LtiResourceLinkInterface` implementation is ready, you can use the [LtiResourceLinkLaunchRequestBuilder](../../src/Message/Launch/Builder/LtiResourceLinkLaunchRequestBuilder.php) to create an LTI Resource Link launch:

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

// Build a launch message
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

As it also returns an [LtiMessageInterface](../../src/Message/LtiMessageInterface.php) instance, you can then use the generated message to launch it as explained previously.

## 2 - Tool side: OIDC initiation

You can find below required steps to handle the initiation an OIDC flow, needed only if you're acting as a tool.

### Handling the OIDC initiation

As a tool, you'll receive an HTTP request containing the [OIDC initiation](https://www.imsglobal.org/spec/security/v1p0#step-2-authentication-request), generated by the platform originating messages builders.

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

### OIDC initiation redirection automation

This library provides the [OidcInitiationRequestHandler](../../src/Security/Oidc/Server/OidcInitiationRequestHandler.php), implementing the [PSR15 RequestHandlerInterface](https://www.php-fig.org/psr/psr-15/#21-psrhttpserverrequesthandlerinterface), that can be exposed in an application controller to automate a redirect response creation from the [OidcInitiator](../../src/Security/Oidc/OidcInitiator.php) output:
- it expects a [PSR7 ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) to handle
- it will return a [PSR7 ResponseInterface](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface) instance to make the redirection to the platform.

For example:

```php
<?php

use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\OidcInitiator;
use OAT\Library\Lti1p3Core\Security\Oidc\Server\OidcInitiationRequestHandler;
use Psr\Http\Message\ServerRequestInterface;

/** @var RegistrationRepositoryInterface $registrationRepository */
$registrationRepository = ...

/** @var ServerRequestInterface $request */
$request = ...

// Create the OIDC initiation handler
$handler = new OidcInitiationRequestHandler(new OidcInitiator($registrationRepository));

// Redirect response from OIDC initiation (including state & nonce generation, via 302)
$response = $handler->handle($request);
```

## 3 - Platform side: OIDC authentication

You can find below required steps to provide authentication during the OIDC flow, needed only if you're acting as a platform.

### Perform the OIDC authentication and redirecting to the tool

After the redirection of the tool to the platform, the platform need to provide authentication and redirect to the tool to continue the OIDC flow.

It can be handled with the [OidcAuthenticator](../../src/Security/Oidc/OidcAuthenticator.php):
- it requires a registration repository and a [UserAuthenticatorInterface](../../src/Security/User/UserAuthenticatorInterface.php) implementation [as explained here](../quickstart/interfaces.md)
- it expects a [PSR7 ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) to handle
- it will output a [LtiMessageInterface](../../src/Message/LtiMessageInterface.php) that cab be used to redirect to the tool to finish the OIDC flow.

You have to specify how to provide platform authentication, for example:

```php
<?php

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use OAT\Library\Lti1p3Core\Security\User\Result\UserAuthenticationResult;
use OAT\Library\Lti1p3Core\Security\User\Result\UserAuthenticationResultInterface;
use OAT\Library\Lti1p3Core\User\UserIdentity;

$userAuthenticator = new class implements UserAuthenticatorInterface
{
   public function authenticate(
       RegistrationInterface $registration,
       string $loginHint
   ): UserAuthenticationResultInterface {
       // Perform user authentication based on the registration, request or login hint
       // (ex: owned session, LDAP, external auth service, etc)
       ...       

        return new UserAuthenticationResult(
           true,                                          // success
           new UserIdentity('userIdentifier', 'userName') // authenticated user identity
       );   
   }
};
```

To then use it to continue OIDC fow:

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

### OIDC authentication redirection automation

This library provides the [OidcAuthenticationRequestHandler](../../src/Security/Oidc/Server/OidcAuthenticationRequestHandler.php), implementing the [PSR15 RequestHandlerInterface](https://www.php-fig.org/psr/psr-15/#21-psrhttpserverrequesthandlerinterface), that can be exposed in an application controller to automate a redirect form response creation from the [OidcAuthenticator](../../src/Security/Oidc/OidcAuthenticator.php) output:
- it expects a [PSR7 ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) to handle
- it will return a [PSR7 ResponseInterface](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface) instance to make the redirection to the tool via a form POST.

For example:

```php
<?php

use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\OidcAuthenticator;
use OAT\Library\Lti1p3Core\Security\Oidc\Server\OidcAuthenticationRequestHandler;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use Psr\Http\Message\ServerRequestInterface;

/** @var RegistrationRepositoryInterface $registrationRepository */
$registrationRepository = ...

/** @var UserAuthenticatorInterface $userAuthenticator */
$userAuthenticator = ...

/** @var ServerRequestInterface $request */
$request = ...

// Create the OIDC authentication handler
$handler = new OidcAuthenticationRequestHandler(new OidcAuthenticator($registrationRepository, $userAuthenticator));

// Redirect response from OIDC authentication (via form POST)
$response = $handler->handle($request);
```

## 4 - Tool side: launch validation

You can find below required steps to validate a platform originating message launch after OIDC flow, needed only if you're acting as a tool.

### Validate the launch

As a tool, you'll receive an HTTP request containing the launch message after OIDC flow completion.

The [ToolLaunchValidator](../../src/Message/Launch/Validator/Tool/ToolLaunchValidator.php) can be used for this:
- it requires a registration repository and a nonce repository implementations [as explained here](../quickstart/interfaces.md)
- it expects a [PSR7 ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) to validate
- it will output a [LaunchValidationResultInterface](../../src/Message/Launch/Validator/Result/LaunchValidationResultInterface.php) representing the launch validation, the related registration and the message payload itself.

For example:
```php
<?php

use OAT\Library\Lti1p3Core\Message\Launch\Validator\Tool\ToolLaunchValidator;
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
    echo $result->getPayload()->getVersion();                  // '1.3.0'
    echo $result->getPayload()->getContext()->getIdentifier(); // 'contextIdentifier'
    echo $result->getPayload()->getClaim('myCustomClaim');     // 'myCustomValue'
    echo $result->getPayload()->getUserIdentity()->getName();  // 'userName', see platform during OIDC authentication
    
    // If needed, you can also access the OIDC state (state parameter)
    echo $result->getState()->getToken()->toString();              // state JWT
    echo $result->getState()->getToken()->getClaims()->get('jti'); // state JWT id

    // If needed, you can also access the validation successes
    foreach ($result->getSuccesses() as $success) {
        echo $success;
    }
} 
```

**Note**: if you want to work with the message payload claims as array of values (for caching purposes for example), you can use the [MessagePayloadClaimsExtractor](../../src/Message/Payload/Extractor/MessagePayloadClaimsExtractor.php) for extracting claims (provides a configurable claim sanitization).

### Working with launch roles

The [LtiMessagePayloadInterface](../../src/Message/Payload/LtiMessagePayloadInterface.php) provides the `getValidatedRoleCollection()` getter to allow you to work easily with [the LTI specification roles](http://www.imsglobal.org/spec/lti/v1p3/#role-vocabularies) as a [RoleCollection](../../src/Role/Collection/RoleCollection.php).

You can base yourself on this collection if you need to perform RBAC on tool side, for example:

```php
<?php

use OAT\Library\Lti1p3Core\Message\Launch\Validator\Result\LaunchValidationResult;
use OAT\Library\Lti1p3Core\Role\RoleInterface;

/** @var LaunchValidationResult $result */
$result = $validator->validatePlatformOriginatingLaunch(...);

// Result exploitation
if (!$result->hasError()) {

    // Access the validated role collection
    $roles = $result->getPayload()->getValidatedRoleCollection();
    
    // Check if a role of type context (core or not) has been provided (our case for http://purl.imsglobal.org/vocab/lis/v2/membership#Learner)
    if ($roles->canFindBy(RoleInterface::TYPE_CONTEXT)) {
        // Authorized launch
        ...
    } else {
        // Unauthorized launch
        ...
    }
} 
```

**Notes**: 
- if the launch contains invalid (non respecting LTI specification) roles, the getter will throw an [LtiException](../../src/Exception/LtiException.php)
- the [LtiMessagePayloadInterface](../../src/Message/Payload/LtiMessagePayloadInterface.php) offers the `getRoles()` getter to work with plain roles values (no validation)
