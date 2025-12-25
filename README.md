# Itsme Laravel Package

[![Latest Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/martin-lechene/itsme-laravel)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Laravel package for authentication via **Itsme** using OpenID Connect 1.0.

## 📋 Description

This package allows you to easily integrate Itsme authentication into your Laravel 12 application. Itsme is a Belgian digital identity solution that allows users to authenticate securely without a password.

## ✨ Features

- ✅ Authentication via OpenID Connect 1.0
- ✅ PKCE support for enhanced security
- ✅ Complete JWT token validation
- ✅ Automatic user account creation
- ✅ Automatic OpenID configuration discovery
- ✅ Ready-to-use Itsme button
- ✅ Complete error handling
- ✅ Laravel 12 compatible
- ✅ Multi-language support (EN/FR)

## 📦 Installation

### 1. Install via Composer

```bash
composer require martin-lechene/itsme-laravel
```

### 2. Publish the configuration

```bash
php artisan vendor:publish --tag=itsme-config
```

### 3. Publish the migrations

```bash
php artisan vendor:publish --tag=itsme-migrations
php artisan migrate
```

### 4. Publish the views (optional)

```bash
php artisan vendor:publish --tag=itsme-views
```

### 5. Publish the language files (optional)

```bash
php artisan vendor:publish --tag=itsme-lang
```

## ⚙️ Configuration

### Environment Variables

Add these variables to your `.env` file:

```env
ITSME_CLIENT_ID=your_client_id
ITSME_CLIENT_SECRET=your_client_secret
ITSME_REDIRECT_URI=https://your-app.com/itsme/callback
ITSME_ENVIRONMENT=sandbox
ITSME_USE_PKCE=true
ITSME_VERIFY_TOKEN=true
```

### Itsme Portal Configuration

1. Create an account on the [Itsme developer portal](https://www.itsme-id.com/en-BE/business/developer)
2. Register your application
3. Configure authorized redirect URIs
4. Get your Client ID and Client Secret
5. Test in sandbox environment

## 🚀 Usage

### Add the Itsme button to your views

In your login view (`resources/views/auth/login.blade.php`):

```blade
<div class="itsme-auth-section">
    <h3>Or sign in with</h3>
    
    @include('itsme::itsme-button', [
        'text' => __('itsme::itsme.button_text'),
        'size' => 'default'
    ])
</div>
```

### Programmatic usage

```php
use ItsmeLaravel\Itsme\Facades\Itsme;

// Get the authorization URL
$url = Itsme::getAuthorizationUrl();
return redirect($url);
```

### Available routes

The package automatically registers these routes:

- `GET /itsme/redirect` - Redirects to the Itsme authentication page
- `GET /itsme/callback` - Handles the callback after authentication

## 📝 User Data Mapping

The package automatically maps Itsme data to your User model:

| Itsme Claim | Laravel Field |
|-------------|---------------|
| `sub` | `itsme_id` |
| `email` | `email` |
| `given_name` | `first_name` |
| `family_name` | `last_name` |
| `name` | `name` |
| `phone_number` | `phone` |

## 🌐 Localization

The package supports multiple languages (English by default, French available). All user-facing strings are translatable.

To customize translations, publish the language files:

```bash
php artisan vendor:publish --tag=itsme-lang
```

Then edit the files in `lang/vendor/itsme/en/itsme.php` or `lang/vendor/itsme/fr/itsme.php`.

The package will automatically use your application's locale (set in `config/app.php`).

## 🎨 Customization

### Customize the button

```blade
@include('itsme::itsme-button', [
    'text' => 'Sign up with itsme®',
    'size' => 'large', // 'small', 'default', 'large'
    'class' => 'custom-class'
])
```

### Customize user creation

You can listen to Laravel events to customize user creation:

```php
use Illuminate\Support\Facades\Event;
use ItsmeLaravel\Itsme\Events\ItsmeUserCreated;

Event::listen(ItsmeUserCreated::class, function ($event) {
    // Customize user creation
    $user = $event->user;
    $userInfo = $event->userInfo;
});
```

## 🔒 Security

The package implements several security measures:

- ✅ **State parameter**: Protection against CSRF attacks
- ✅ **Nonce**: Protection against replay attacks
- ✅ **PKCE**: Protection against authorization code interception
- ✅ **Token validation**: Verification of signature, expiration, audience, issuer
- ✅ **Redirect URI validation**: Verification that the URI matches the configuration

## 🧪 Testing

```bash
composer test
```

## 📚 Documentation

For more information, see:

- [Package Plan](PLAN_PACKAGE_ITSME.md)
- [Technical Details](DETAILS_TECHNIQUES.md)
- [Authentication Flow](FLUX_AUTHENTIFICATION.md)
- [Usage Examples](USAGE_EXAMPLES.md)
- [Official Itsme Documentation](https://www.itsme-id.com/en-BE/business/developer)

## 🤝 Contributing

Contributions are welcome! Feel free to open an issue or a pull request.

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## 📄 License

This package is licensed under the [MIT License](LICENSE).

## 🙏 Acknowledgments

- [Itsme](https://www.itsme-id.com/) for their digital identity service
- [Laravel](https://laravel.com/) for the framework
- [Laravel Socialite](https://laravel.com/docs/socialite) for inspiration

## 📞 Support

For any questions or issues, please open an [issue](https://github.com/martin-lechene/itsme-laravel/issues).

---

**Note**: This package is not officially supported by Itsme. It is a community implementation based on Itsme's public documentation.
