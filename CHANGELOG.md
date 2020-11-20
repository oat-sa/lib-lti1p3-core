CHANGELOG
=========

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
* Fixed core service client audience for access token requests
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
