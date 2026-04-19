# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Laravel 13 support (`^13.0`) — fully tested with orchestra/testbench `^11.0`
- PHP 8.4 support in CI matrix
- `itsme.auth` middleware alias registered automatically by the service provider
- `itsme_auth_required` translation key (EN + FR) used by the `RequireItsmeAuth` middleware
- Comprehensive test infrastructure: test `users` table migration, test `User` model, `APP_KEY` in `phpunit.xml`, `login` route for feature tests

### Changed
- **[BREAKING]** `minimum-stability` changed from `dev` to `stable` — ensures only stable releases are resolved
- `laravel/socialite` removed from `require` — it was never used by the package
- Composer scripts now use `vendor/bin/` prefixes (`vendor/bin/phpunit`, `vendor/bin/phpstan`, `vendor/bin/php-cs-fixer`)
- `ItsmeServiceProvider`: removed non-existent `itsme-assets` publish group; middleware registration extracted to `registerMiddleware()` method; uses `Router` directly instead of `Route` facade
- `ItsmeController`: `redirect()` and `callback()` now declare `\Illuminate\Http\RedirectResponse` return types
- `RequireItsmeAuth`: hardcoded French string replaced by `__('itsme::itsme.errors.itsme_auth_required')`
- CI: `fail-fast` changed to `false` so all matrix combinations are reported; `imagick` extension removed (optional, not required by the package)
- CI: PHPStan and PHP-CS-Fixer jobs use PHP 8.3 instead of 8.2
- README.md: complete rewrite with full API documentation, events, middleware, artisan, security checklist, upgrade guide, and project structure
- README.fr.md: complete rewrite in French, synchronized with English README

### Fixed
- `OpenIdDiscovery::discover()`: `empty($config)` changed to `$config === null` so an empty config array reaches the correct endpoint-not-found exception
- Migration `down()`: explicit `dropUnique()` / `dropIndex()` before `dropColumn()` for SQLite compatibility
- `TokenValidatorTest`: removed unused `$this->discovery` mock (was triggering PHPUnit 12 "no expectations" notice)
- `ItsmeServiceTest`: added `#[AllowMockObjectsWithoutExpectations]` attribute; fixed expected exception message from French to English

### Security
- `firebase/php-jwt` upgraded from `^6.0` → `^6.10` → **`^7.0`** (v7.0.5): all 6.x versions blocked by Packagist advisory `PKSA-y2cr-5h3j-g3ys`; v7 is clean and has the same `Key`/`JWT` API surface
- `phpunit/phpunit` upgraded from `^11.0` to `^12.5.22` (patches argument injection via newline CVE)

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

