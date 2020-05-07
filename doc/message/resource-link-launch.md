# LTI resource link launch process

> How to perform a [LTI launch](http://www.imsglobal.org/spec/lti/v1p3#lti-launch-0) for a [LTI resource link](http://www.imsglobal.org/spec/lti/v1p3#resource-link-0) as a platform, and validate it as a tool.

## Table of contents

- [Platform side](#platform-side)
- [Tool side](#tool-side)

## Platform side

You can find below required steps to generate a LTI launch request, needed only if you're acting as a platform.

### Create the resource link

A [LTI resource link](http://www.imsglobal.org/spec/lti/v1p3#resource-link-0) represent a resource made available from a tool to a platform.

First of all, you need to create a [ResourceLink](../../src/Link/ResourceLink/ResourceLinkInterface.php) instance:
```php
<?php

use OAT\Library\Lti1p3Core\Link\ResourceLink\ResourceLink;

$resourceLink = new ResourceLink(
    'resourceLinkIdentifier',  // [required] identifier
    'resourceLinkUrl',         // [optional] url of the resource on the tool
    'title',                   // [optional] title
    'description'              // [optional] description
);
```
**Notes**:
- if no resource link url is given, the launch will be done on the default launch url of the registered tool
- since the platform should be able to retrieve resource links from database for example (pre fetched), you can implement your own [ResourceLinkInterface](../../src/Link/ResourceLink/ResourceLinkInterface.php)

### Create a launch request for the resource link

Once your `ResourceLinkInterface` implementation is ready, you need to launch it to a registered tool, within the context of a registration.

To do so, you can use the [LtiLaunchRequestBuilder](../../src/Launch/Builder/LtiLaunchRequestBuilder.php) to create an anonymous launch request:
```php
<?php

use OAT\Library\Lti1p3Core\Launch\Builder\LtiLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Message\Claim\ContextClaim;

// Create the builder
$builder = new LtiLaunchRequestBuilder();

// Get related registration of the launch
/** @var RegistrationRepositoryInterface $registrationRepository */
$registration = $registrationRepository->find(...);

// Create an anonymous launch request
$launchRequest = $builder->buildResourceLinkLtiLaunchRequest(
    $resourceLink,
    $registration,
    null, // will use the registration default deployment id, but you can pass a specific one
    [
        'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner' // role
    ], 
    [
        new ContextClaim('contextId'),     // LTI claim representing the context 
        'myCustomClaim' => 'myCustomValue' // custom claim
    ]
);
```
**Note**: like the `ContextClaim` class, any claim that implement the [MessageClaimInterface](../../src/Message/Claim/MessageClaimInterface.php) will be automatically normalized and added to the message's claims.

You can also create a user launch request by providing to the builder your own [UserIdentityInterface](../../src/User/UserIdentityInterface.php) implementation:
```php
<?php

use OAT\Library\Lti1p3Core\User\UserIdentity;

// Provide your user identity
$userIdentity = new UserIdentity(
    'userIdentifier',
    'userName',
    'user@email.com'
);

// Create a launch request for this user
$launchRequest = $builder->buildUserResourceLinkLtiLaunchRequest(
    $resourceLink,
    $registration,
    $userIdentity,
    null, 
    [
        'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner' // role
    ]
);
```

As a result of the build, you get a [LtiLaunchRequest](../../src/Launch/Request/LtiLaunchRequest.php) instance that can be used in several ways:
```php
<?php

use OAT\Library\Lti1p3Core\Launch\Request\LtiLaunchRequest;

/** @var LtiLaunchRequest $launchRequest */

// Main properties you can use as you want to offer the launch to the platform users
echo $launchRequest->getUrl();        // url of the launch
echo $launchRequest->getParameters(); // parameters of the launch

// Or use those helpers methods to ease the launch interactions
echo $launchRequest->toUrl();              // url with launch parameters as query parameters
echo $launchRequest->toHtmlLink();         // HTML link, where href is the output url
echo $launchRequest->toHtmlRedirectForm(); // HTML hidden form, with possibility of auto redirection
```

All you need to do now is to present this launch request to the users, to launch them to the tool.

## Tool side

You can find below required steps to validate a LTI launch request, needed only if you're acting as a tool.

### Validate the launch request

As a tool, you'll receive an HTTP request containing the [launch request](http://www.imsglobal.org/spec/lti/v1p3#resource-link-launch-request-message).

The [LtiLaunchRequestValidator](../../src/Launch/Validator/LtiLaunchRequestValidator.php) can be used for this:
- it requires a registration repository and a nonce repository implementations [as explained here](../quickstart/interfaces.md)
- it expects a [PSR7 ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) to validate
- and it will output a [LtiLaunchRequestValidationResult](../../src/Launch/Validator/LtiLaunchRequestValidationResult.php) representing the launch validation and the message itself.

By example:
```php
<?php

use OAT\Library\Lti1p3Core\Launch\Validator\LtiLaunchRequestValidator;
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
$validator = new LtiLaunchRequestValidator($registrationRepository, $nonceRepository);

// Perform validation
$result = $validator->validate($request);

// Result exploitation
if (!$result->hasFailures()) {
    echo $result->getLtiMessage()->getVersion();              // '1.3.0'
    echo $result->getLtiMessage()->getContext()->getId();     // 'contextId'
    echo $result->getLtiMessage()->getClaim('myCustomClaim'); // 'myCustomValue'
    echo $result->getLtiMessage()->getUserIdentity();         // same as above $userIdentity 
} 
```