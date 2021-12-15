CHANGELOG
=========

6.7.0
-----

* Add LtiSystemRole supporting [TestUser role](https://www.imsglobal.org/spec/lti/v1p3/#lti-vocabulary-for-system-roles)

6.6.0
-----

* Added MessagePayloadClaimsExtractor to ease message claims extraction
* Updated documentation

6.5.0
-----

* Extended psr/log dependency versions

6.4.0
-----

* Extended psr/cache dependency versions

6.3.1
-----

* Fixed [CVE-2021-41106](https://github.com/advisories/GHSA-7322-jrq4-x5hf) issue

6.3.0
-----

* Added [submission review service](https://www.imsglobal.org/spec/lti-sr/v1p0) support (validator and claims)

6.2.0
-----

* Added [proctoring end assessment message](https://www.imsglobal.org/spec/proctoring/v1p0#h.ooq616k28cwm) tool launch validator support
* Fixed issue [#128](https://github.com/oat-sa/lib-lti1p3-core/issues/128)

6.1.0
-----

* Added claim support for [proctoring end assessment message](https://www.imsglobal.org/spec/proctoring/v1p0#h.ooq616k28cwm)

6.0.1
-----

* Fixed issue [#119](https://github.com/oat-sa/lib-lti1p3-core/issues/119)

6.0.0
-----

* Added [migration guide](https://github.com/oat-sa/lib-lti1p3-core/wiki/Migration-from-5.x-to-6.x) to document breaking changes and migration steps
* Added LaunchValidatorInterface, PlatformLaunchValidatorInterface and ToolLaunchValidatorInterface
* Added AccessTokenResponseGeneratorInterface
* Added RequestAccessTokenValidatorInterface and RequestAccessTokenValidationResultInterface
* Moved PlatformLaunchValidator in Platform sub namespace  
* Moved ToolLaunchValidator in Tool sub namespace
* Fixed LtiServiceServer media type handling
* Fixed LtiServiceClient grant assertion aud claim  
* Fixed AgsClaim with not mandatory line item container url 
* Fixed OAuth2 token validation to support multiple audiences 
* Fixed proctoring start assessment message validator with resource link check 
* Updated LtiServiceServerRequestHandlerInterface signature
* Updated Guzzle dependency to ^6.5 || ^7.0 
* Updated documentation

5.0.1
-----

* Fixed [CVE-2021-30130 issue](https://github.com/advisories/GHSA-vf4w-fg7r-5v94)

5.0.0
-----

* Added [migration guide](https://github.com/oat-sa/lib-lti1p3-core/wiki/Migration-from-4.x-to-5.x) to document breaking changes and migration steps
* Added psalm support
* Added support of nullable error for Result based classes
* Added LtiServiceServer component to ease providing LTI services
* Moved Service\Server namespace into Security\OAuth2
* Moved UserAuthenticationResultInterface in Result sub namespace
* Moved UserAuthenticationResult in Result sub namespace
* Renamed JwksServer into JwksRequestHandler
* Renamed OidcInitiationServer into OidcInitiationRequestHandler
* Renamed OidcAuthenticationServer into OidcAuthenticationRequestHandler
* Renamed AccessTokenRequestValidator into RequestAccessTokenValidator
* Renamed AccessTokenRequestValidatorResult into RequestAccessTokenValidatorResult
* Renamed ServiceClientInterface into LtiServiceClientInterface
* Renamed ServiceClient into LtiServiceClient
* Fixed globally nullable parameters for classes constructors  
* Fixed deprecated legacy user identifier claim
* Updated UserAuthenticatorInterface signature  
* Updated documentation

4.2.0
-----

* Added enhanced role management: type (system, institution, context), core / non core, long / short names & automatic validation
* Updated LtiMessagePayloadInterface with getValidatedRoleCollection() method (allows easy access to validated roles from launches)
* Updated documentation

4.1.0
-----

* Added invalid access token cache busting on 401 LTI service response (with auto retry)

4.0.0
-----

* Added [migration guide](https://github.com/oat-sa/lib-lti1p3-core/wiki/Migration-from-3.x-to-4.x) to document breaking changes and migration steps
* Added PHP 8 support (and kept >=7.2)
* Added algorithms support for RS384/512, HS256/384/512, ES256/384/512 (on top of RS256)
* Added wrapper interfaces for JWT handling (builder, parser, validator), with default implementation based on [lcobucci/jwt](https://github.com/lcobucci/jwt)
* Added multiple audiences support in JWT handling
* Added collection, result and ids generator utils
* Added more security testing tools 
* Fixed issue [#74](https://github.com/oat-sa/lib-lti1p3-core/issues/74)
* Fixed ServiceClient to work with 201 access token endpoint response  
* Updated documentation

3.3.1
-----

* Updated the version of ramsey/uuid dependency to allow the use of version 4 

3.3.0
-----

* Added OidcTestingTrait to ease OIDC based testing flows 

3.2.2
-----

* Fixed lcobucci/jwt dependency to version 3.3.3

3.2.1
-----

* Added fallback to JWKS lookup to check URL if key is not found in cache

3.2.0
-----

* Added possibility to specify allowed scopes for service calls validation
* Fixed service client repository audience check
* Updated documentation

3.1.1
-----

* Fixed DeepLinkingSettingsClaim boolean properties handling (select multiple, auto create)

3.1.0
-----

* Added PSR15 support for OIDC (init and auth) server components
* Added possibility to reset the MessagePayloadBuilder to allow multiple generation
* Added possibility to add several claims at once on the MessagePayloadBuilder
* Added tool originating DeepLinking response messages stronger validation (on settings data claim)
* Updated documentation

3.0.0
-----

* Added Travis integration
* Added claims handling for DeepLinking, ACS, and Proctoring
* Added PSR7 aware components to automate JWKS and OIDC (init and auth) exposition
* Added content item resources for DeepLinking (form DeepLinking specifications)
* Added new core message layer foundations (new interfaces and abstractions)
* Added core tool originating message layer (builder, validator, result) based on new foundations
* Reworked (breaking changes) core platform originating message layer (builder, validator, result) based on new foundations
* Fixed issue [#46](https://github.com/oat-sa/lib-lti1p3-core/issues/46)
* Updated php dependency to >= 7.2.0
* Updated phpunit dependency to 8.5.8
* Updated documentation

2.4.0
-----

* Added Basic Outcome claim handling

2.3.0
-----

* Added UserIdentityFactoryInterface
* Added NRPS claim getter on LtiMessageInterface

2.2.0
-----

* Added UserIdentityFactory

2.1.0
-----

* Added additional properties handling to the UserIdentity
* Added NRPS claim handling
* Adapted JWT validations to test expiry first, to spare useless checks
* Adapted tool message validator to match IMS certification requirements

2.0.4
-----

* Fixed ServiceClient access tokens caching scoping

2.0.3
-----

* Fixed JwksExporter output structure

2.0.2
-----

* Fixed OidcAuthenticationRequest parameters exposition

2.0.1
-----

* Fixed ServiceClient header


2.0.0
-----

* Updated AccessTokenResponseGenerator to generate for a key chain instead of a registration

1.2.0
-----

* Added getOidcState() method to LtiLaunchRequestValidationResult

1.1.0
-----

* Added findAll() method to RegistrationRepositoryInterface

1.0.0
-----

* Provided core messages implementation and documentation
* Provided core services implementation and documentation
