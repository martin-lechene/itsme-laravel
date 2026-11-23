# Exemples d'Utilisation - Package Itsme Laravel

## 📝 Table des Matières

1. [Installation de base](#installation-de-base)
2. [Configuration](#configuration)
3. [Utilisation dans les vues](#utilisation-dans-les-vues)
4. [Utilisation programmatique](#utilisation-programmatique)
5. [Personnalisation](#personnalisation)
6. [Gestion des erreurs](#gestion-des-erreurs)

---

## Installation de base

### 1. Installation via Composer

```bash
composer require martin-lechene/itsme-laravel
```

### 2. Publier les fichiers de configuration

```bash
# Configuration
php artisan vendor:publish --tag=itsme-config

# Migrations
php artisan vendor:publish --tag=itsme-migrations

# Vues (optionnel)
php artisan vendor:publish --tag=itsme-views
```

### 3. Exécuter les migrations

```bash
php artisan migrate
```

### 4. Configurer les variables d'environnement

Dans votre fichier `.env` :

```env
ITSME_CLIENT_ID=your_client_id_from_portal
ITSME_CLIENT_SECRET=your_client_secret_from_portal
ITSME_REDIRECT_URI=https://your-app.com/itsme/callback
ITSME_ENVIRONMENT=sandbox
ITSME_USE_PKCE=true
ITSME_VERIFY_TOKEN=true
```

---

## Configuration

### Fichier de configuration (`config/itsme.php`)

Le package publie un fichier de configuration que vous pouvez personnaliser :

```php
return [
    'client_id' => env('ITSME_CLIENT_ID'),
    'client_secret' => env('ITSME_CLIENT_SECRET'),
    'redirect' => env('ITSME_REDIRECT_URI', '/itsme/callback'),
    'environment' => env('ITSME_ENVIRONMENT', 'sandbox'),
    'scopes' => [
        'openid',
        'profile',
        'email',
        'phone',
    ],
    'use_pkce' => env('ITSME_USE_PKCE', true),
    'verify_token_signature' => env('ITSME_VERIFY_TOKEN', true),
];
```

---

## Utilisation dans les vues

### Exemple 1 : Page de connexion simple

```blade
{{-- resources/views/auth/login.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Connexion') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <!-- Vos champs de connexion classiques -->
                    </form>

                    <div class="mt-4 text-center">
                        <p class="text-muted">Ou</p>
                        @include('itsme::itsme-button', [
                            'text' => 'Se connecter avec itsme®'
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

### Exemple 2 : Page d'inscription

```blade
{{-- resources/views/auth/register.blade.php --}}
<div class="registration-section">
    <h2>Créer un compte</h2>
    
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <!-- Vos champs d'inscription -->
    </form>

    <div class="divider">
        <span>Ou</span>
    </div>

    @include('itsme::itsme-button', [
        'text' => 'S\'inscrire avec itsme®',
        'size' => 'large'
    ])
</div>
```

### Exemple 3 : Bouton personnalisé

```blade
@include('itsme::itsme-button', [
    'text' => 'Connexion rapide avec itsme®',
    'size' => 'large', // 'small', 'default', 'large'
    'class' => 'my-custom-class btn-primary'
])
```

---

## Utilisation programmatique

### Exemple 1 : Redirection manuelle

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use ItsmeLaravel\Itsme\Facades\Itsme;

class AuthController extends Controller
{
    public function redirectToItsme()
    {
        $url = Itsme::getAuthorizationUrl();
        return redirect($url);
    }
}
```

### Exemple 2 : Utilisation dans une route

```php
// routes/web.php
use ItsmeLaravel\Itsme\Facades\Itsme;

Route::get('/login/itsme', function () {
    return redirect(Itsme::getAuthorizationUrl());
})->name('login.itsme');
```

### Exemple 3 : Gestion personnalisée du callback

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use ItsmeLaravel\Itsme\Services\ItsmeService;
use Illuminate\Support\Facades\Auth;

class CustomItsmeController extends Controller
{
    public function __construct(
        protected ItsmeService $itsmeService
    ) {}

    public function handleCallback(Request $request)
    {
        try {
            $userInfo = $this->itsmeService->handleCallback($request);
            
            // Logique personnalisée
            $user = $this->createOrUpdateUser($userInfo);
            
            // Connexion
            Auth::login($user);
            
            // Redirection personnalisée
            return redirect()->route('dashboard');
            
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Erreur d\'authentification');
        }
    }
    
    protected function createOrUpdateUser(array $userInfo)
    {
        // Votre logique personnalisée
    }
}
```

---

## Personnalisation

### Personnaliser le mapping des données utilisateur

Le contrôleur par défaut mappe automatiquement les champs, mais vous pouvez créer votre propre contrôleur :

```php
protected function createOrUpdateUser(array $userInfo)
{
    $user = User::where('itsme_id', $userInfo['sub'])
        ->orWhere('email', $userInfo['email'])
        ->first();

    $data = [
        'itsme_id' => $userInfo['sub'],
        'email' => $userInfo['email'],
        'first_name' => $userInfo['given_name'] ?? null,
        'last_name' => $userInfo['family_name'] ?? null,
        'phone' => $userInfo['phone_number'] ?? null,
        'email_verified_at' => $userInfo['email_verified'] ? now() : null,
    ];

    if ($user) {
        $user->update($data);
    } else {
        $user = User::create($data);
    }

    return $user;
}
```

### Écouter les événements (si implémentés)

```php
use Illuminate\Support\Facades\Event;

// Dans votre AppServiceProvider ou EventServiceProvider
Event::listen('itsme.user.created', function ($user, $userInfo) {
    // Envoyer un email de bienvenue
    Mail::to($user->email)->send(new WelcomeEmail($user));
});

Event::listen('itsme.user.authenticated', function ($user) {
    // Logger la connexion
    Log::info('User authenticated via Itsme', ['user_id' => $user->id]);
});
```

### Personnaliser les styles du bouton

Créez votre propre vue en publiant les vues :

```bash
php artisan vendor:publish --tag=itsme-views
```

Puis modifiez `resources/views/vendor/itsme/itsme-button.blade.php` :

```blade
<a href="{{ route('itsme.redirect') }}" 
   class="my-custom-itsme-button">
    <!-- Votre design personnalisé -->
</a>
```

---

## Gestion des erreurs

### Exemple 1 : Page d'erreur personnalisée

```blade
{{-- resources/views/errors/itsme.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="alert alert-danger">
        <h4>Erreur d'authentification Itsme</h4>
        <p>{{ $error ?? 'Une erreur est survenue' }}</p>
        <a href="{{ route('login') }}" class="btn btn-primary">
            Retour à la connexion
        </a>
    </div>
</div>
@endsection
```

### Exemple 2 : Gestion dans le contrôleur

```php
public function callback(Request $request)
{
    try {
        $userInfo = $this->itsmeService->handleCallback($request);
        // ...
    } catch (\ItsmeLaravel\Itsme\Exceptions\InvalidStateException $e) {
        return redirect()->route('login')
            ->with('error', 'Session expirée. Veuillez réessayer.');
    } catch (\ItsmeLaravel\Itsme\Exceptions\AuthenticationFailedException $e) {
        return redirect()->route('login')
            ->with('error', $e->getMessage());
    } catch (\Exception $e) {
        Log::error('Itsme authentication error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        return redirect()->route('login')
            ->with('error', 'Une erreur inattendue est survenue.');
    }
}
```

---

## Tests

### Exemple de test unitaire

```php
<?php

namespace Tests\Unit;

use ItsmeLaravel\Itsme\Tests\TestCase;
use ItsmeLaravel\Itsme\Services\ItsmeService;
use Illuminate\Support\Facades\Http;

class ItsmeServiceTest extends TestCase
{
    public function test_generates_authorization_url()
    {
        Http::fake([
            '*/.well-known/openid-configuration' => Http::response([
                'authorization_endpoint' => 'https://idp.itsme.be/authorize',
                'token_endpoint' => 'https://idp.itsme.be/token',
                'userinfo_endpoint' => 'https://idp.itsme.be/userinfo',
            ]),
        ]);

        $service = app(ItsmeService::class);
        $url = $service->getAuthorizationUrl();

        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('client_id=', $url);
    }
}
```

---

## Intégration avec Breeze/Jetstream

### Laravel Breeze

Dans `resources/views/auth/login.blade.php` :

```blade
<div class="mt-6">
    <div class="relative">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-300"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-2 bg-white text-gray-500">Ou</span>
        </div>
    </div>

    <div class="mt-6">
        @include('itsme::itsme-button', [
            'text' => 'Se connecter avec itsme®',
            'class' => 'w-full justify-center'
        ])
    </div>
</div>
```

### Laravel Jetstream

Dans `resources/views/auth/login.blade.php` :

```blade
<x-jet-section-border />

<div class="mt-6">
    <x-jet-label value="Ou connectez-vous avec" />
    <div class="mt-4">
        @include('itsme::itsme-button', [
            'text' => 'Se connecter avec itsme®'
        ])
    </div>
</div>
```

---

## Dépannage

### Problème : "Invalid state parameter"

**Solution** : Vérifiez que les sessions fonctionnent correctement et que le state est bien stocké en session.

### Problème : "Token exchange failed"

**Solution** : Vérifiez vos credentials (Client ID et Client Secret) et que le redirect URI correspond exactement à celui configuré dans le portail Itsme.

### Problème : "Discovery failed"

**Solution** : Vérifiez votre connexion internet et que l'URL de découverte est correcte. Vous pouvez aussi définir manuellement les endpoints dans la configuration.

---

Pour plus d'informations, consultez le [README.md](README.md) principal.

