# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-01

### Added
- Initial release
- OpenID Connect 1.0 authentication flow
- PKCE support for enhanced security
- JWT token validation
- Automatic user creation/update
- OpenID Connect discovery
- Itsme button component
- Comprehensive error handling
- Laravel 12 compatibility
- Migration for itsme_id field
- Facade for easy access
- Service Provider for Laravel integration

### Security
- State parameter validation (CSRF protection)
- Nonce validation (replay attack protection)
- PKCE implementation
- JWT signature verification
- Token expiration validation
- Audience and issuer validation

