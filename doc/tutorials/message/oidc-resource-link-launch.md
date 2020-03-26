# LTI resource link launch process with OpenId Connect login initiation flow

> How to perform a [OIDC LTI launch](https://www.imsglobal.org/spec/security/v1p0#openid_connect_launch_flow) for a [LTI resource link](http://www.imsglobal.org/spec/lti/v1p3#resource-link-0).

## Platform side: OIDC launch request generation

You can find below required steps to generate a OIDC LTI launch request, needed only if you're acting as a platform.

### Create the resource link

A [LTI resource link](http://www.imsglobal.org/spec/lti/v1p3#resource-link-0) represent a resource made available from a tool to a platform.

First of all, you need to create a [ResourceLink](../../../src/Link/ResourceLink/ResourceLinkInterface.php) instance:
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
- if no resource link url is given, the launch will be done on the default launch url of the deployed tool
- since the platform can retrieve them from database for example, you can implement your own [ResourceLinkInterface](../../../src/Link/ResourceLink/ResourceLinkInterface.php)

### Create a OIDC launch request for the resource link

Once your `ResourceLinkInterface` implementation is ready, you need to launch it to a deployed tool following the [OIDC workflow](https://www.imsglobal.org/spec/security/v1p0#openid_connect_launch_flow), within the context of a deployment.

To do so, you can use the [OidcLaunchRequestBuilder](../../../src/Launch/Builder/OidcLaunchRequestBuilder.php) to create an OIDC launch request:
```php
<?php

use OAT\Library\Lti1p3Core\Launch\Builder\OidcLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Deployment\DeploymentRepositoryInterface;
use OAT\Library\Lti1p3Core\Message\Claim\ContextClaim;

// Create the builder
$builder = new OidcLaunchRequestBuilder();

// Get related deployment of the launch
/** @var DeploymentRepositoryInterface $repository */
$deployment = $repository->find(...);

// Create a OIDC launch request
$launchRequest = $builder->buildResourceLinkOidcLaunchRequest(
    $resourceLink,
    $deployment,
    'loginHint', // hint about the user login process that will be done on a later step
    [
        'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner' // role
    ], 
    [
        new ContextClaim('contextId'),     // LTI claim representing the context 
        'myCustomClaim' => 'myCustomValue' // custom claim
    ]
);
```
**Note**: like the `ContextClaim` class, any claim that implement the [MessageClaimInterface](../../../src/Message/Claim/MessageClaimInterface.php) will be automatically normalized and added to the message's claims.

As a result of the build, you get a [OidcLaunchRequest](../../../src/Launch/Request/OidcLaunchRequest.php) instance that can be used in several ways:
```php
<?php

use OAT\Library\Lti1p3Core\Launch\Request\OidcLaunchRequest;

/** @var OidcLaunchRequest $launchRequest */

// Main properties you can use as you want to offer the launch to the platform users
echo $launchRequest->getUrl();             // url of the launch
echo $launchRequest->getParameters();      // parameters of the launch

// Or use those helpers methods to ease the launch interactions
echo $launchRequest->toUrl();              // url with launch parameters as query parameters
echo $launchRequest->toHtmlLink();         // HTML link, where href is the output url
echo $launchRequest->toHtmlRedirectForm(); // HTML hidden form, with possibility of auto redirection
```

All you need to do now is to present this OIDC launch request to the users, to launch them to the tool.

## Tool side: OIDC login initiation

You can find below required steps to initiate an OIDC login, needed only if you're acting as a tool.

### Initiate the login

As a tool, you'll receive an HTTP request containing the [OIDC launch request login initiation](https://www.imsglobal.org/spec/security/v1p0#step-2-authentication-request).

You can use the [LtiLaunchRequestValidator](../../../src/Security/Oidc/Endpoint/OidcLoginInitiator.php) to handle this:
- it requires a deployment repository implementation [as explained here](../interfaces.md)
- it expect a [PSR7 ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) to handle
- and it will output a [OidcAuthenticationRequest](../../../src/Security/Oidc/Request/OidcAuthenticationRequest.php) to be sent back to the platform.

By example:
```php
<?php

use OAT\Library\Lti1p3Core\Deployment\DeploymentRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\Endpoint\OidcLoginInitiator;
use Psr\Http\Message\ServerRequestInterface;

/** @var DeploymentRepositoryInterface $deploymentRepository */
$deploymentRepository = ...

/** @var ServerRequestInterface $request */
$request = ...

// Create the OIDC login initiator
$initiator = new OidcLoginInitiator($deploymentRepository);

// Perform the login initiation (including state generation)
$oidcAuthenticationRequest = $initiator->initiate($request);

// Redirection to the platform
header('Location: ' . $oidcAuthenticationRequest->toUrl(), true, 302);
die;
```

## Platform side: OIDC authentication

You can find below required steps to authenticate an OIDC login and performing a launch, needed only if you're acting as a platform.

### Perform the authentication and launching the resource link

After the redirection of the tool to the platform, the platform will receive as a HTTP request the [OidcAuthenticationRequest](../../../src/Security/Oidc/Request/OidcAuthenticationRequest.php).

It can be handled with the [OidcLoginAuthenticator](../../../src/Security/Oidc/Endpoint/OidcLoginAuthenticator.php):
- it requires a deployment repository and a user authenticator implementation [as explained here](../interfaces.md)
- it expect a [PSR7 ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) to handle
- and it will output a [LtiLaunchRequest](../../../src/Launch/Request/LtiLaunchRequest.php) to be sent back to the platform.

By example:
```php
<?php

use OAT\Library\Lti1p3Core\Deployment\DeploymentRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\Endpoint\OidcLoginAuthenticator;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use Psr\Http\Message\ServerRequestInterface;

/** @var DeploymentRepositoryInterface $deploymentRepository */
$deploymentRepository = ...

/** @var UserAuthenticatorInterface $userAuthenticator */
$userAuthenticator = ...

/** @var ServerRequestInterface $request */
$request = ...

// Create the OIDC login initiator
$authenticator = new OidcLoginAuthenticator($deploymentRepository, $userAuthenticator);

// Perform the login authentication (delegating to the $userAuthenticator with the hint 'loginHint')
$launchRequest = $authenticator->authenticate($request);

// Auto redirection to the tool via the  user's browser
echo $launchRequest->toHtmlRedirectForm();
```

## Tool side: validating the launch after OIDC

You can find below required steps to validate a LTI launch request, needed only if you're acting as a tool.

### Validate the launch request after OIDC

As a tool, you'll receive an HTTP request containing the [launch request](http://www.imsglobal.org/spec/lti/v1p3#resource-link-launch-request-message).

The [LtiLaunchRequestValidator](../../../src/Launch/Validator/LtiLaunchRequestValidator.php) can be used for this:
- it requires a deployment repository and a nonce repository implementations [as explained here](../interfaces.md)
- it expect a [PSR7 ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) to validate
- and it will output a [LtiLaunchRequestValidationResult](../../../src/Launch/Validator/LtiLaunchRequestValidationResult.php) representing the launch validation and the message itself.

By example:
```php
<?php

use OAT\Library\Lti1p3Core\Launch\Validator\LtiLaunchRequestValidator;
use OAT\Library\Lti1p3Core\Deployment\DeploymentRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;

/** @var DeploymentRepositoryInterface $deploymentRepository */
$deploymentRepository = ...

/** @var NonceRepositoryInterface $nonceRepository */
$nonceRepository = ...

/** @var ServerRequestInterface $request */
$request = ...

// Create the validator
$validator = new LtiLaunchRequestValidator($deploymentRepository, $nonceRepository);

// Perform validation
$result = $validator->validate($request);

// Result exploitation
if (!$result->hasFailures()) {
    echo $result->getLtiMessage()->getVersion();              // '1.3.0'
    echo $result->getLtiMessage()->getContext()->getId();     // 'contextId'
    echo $result->getLtiMessage()->getClaim('myCustomClaim'); // 'myCustomValue'
    echo $result->getLtiMessage()->getUserIdentity();         // given by the platform at OIDC authentication
} 
```