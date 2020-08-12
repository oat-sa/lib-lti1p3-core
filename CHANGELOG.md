CHANGELOG
=========

2.1.0
-----

* Added additional properties handling to the UserIdentity
* Fixed JWT validations to test expiry first, to spare useless checks
* Adapted tool message validator to match IMS certification requirements

2.0.4
-----

* Fixed `ServiceClient` access tokens caching scoping

2.0.3
-----

* Fixed `JwksExporter` output structure

2.0.2
-----

* Fixed `OidcAuthenticationRequest` parameters exposition

2.0.1
-----

* Fixed `ServiceClient` header


2.0.0
-----

* Updated `AccessTokenResponseGenerator` to generate for a key chain instead of a registration

1.2.0
-----

* Added `getOidcState()` method to `LtiLaunchRequestValidationResult`

1.1.0
-----

* Added `findAll()` method to `RegistrationRepositoryInterface`

1.0.0
-----

* Provided core messages implementation and documentation
* Provided core services implementation and documentation
