<?php

namespace ItsmeLaravel\Itsme\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use ItsmeLaravel\Itsme\Events\ItsmeAuthenticationFailed;
use ItsmeLaravel\Itsme\Events\ItsmeUserAuthenticated;
use ItsmeLaravel\Itsme\Events\ItsmeUserCreated;
use ItsmeLaravel\Itsme\Exceptions\AuthenticationFailedException;
use ItsmeLaravel\Itsme\Exceptions\InvalidStateException;
use ItsmeLaravel\Itsme\Exceptions\InvalidTokenException;
use ItsmeLaravel\Itsme\Services\ItsmeService;

class ItsmeController
{
    public function __construct(
        protected ItsmeService $itsmeService
    ) {
    }

    /**
     * Redirect the user to the Itsme authorization page.
     */
    public function redirect(): \Illuminate\Http\RedirectResponse
    {
        try {
            $url = $this->itsmeService->getAuthorizationUrl();

            return redirect($url);
        } catch (\Exception $e) {
            Log::error('Itsme redirect failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')
                ->with('error', __('itsme::itsme.errors.redirect_failed'));
        }
    }

    /**
     * Handle the callback from Itsme.
     */
    public function callback(Request $request): \Illuminate\Http\RedirectResponse
    {
        try {
            $userInfo = $this->itsmeService->handleCallback($request);

            // Create or update user
            $isNewUser = !$this->userExists($userInfo);
            $user = $this->createOrUpdateUser($userInfo);

            // Emit events
            if ($isNewUser) {
                Event::dispatch(new ItsmeUserCreated($user, $userInfo));
            }
            Event::dispatch(new ItsmeUserAuthenticated($user, $userInfo));

            // Prevent session fixation before authenticating
            session()->regenerate();

            // Log in the user
            Auth::login($user, true);

            // Redirect to intended page or home (sanitized against open redirects)
            return redirect($this->safeIntendedUrl());

        } catch (InvalidStateException $e) {
            Log::warning('Itsme invalid state', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->with('error', __('itsme::itsme.errors.session_expired'));

        } catch (AuthenticationFailedException $e) {
            Log::error('Itsme authentication failed', [
                'error' => $e->getMessage(),
            ]);

            Event::dispatch(new ItsmeAuthenticationFailed(
                $e->getMessage(),
                $request->get('error_description')
            ));

            return redirect()->route('login')
                ->with('error', $e->getMessage());

        } catch (InvalidTokenException $e) {
            Log::error('Itsme invalid token', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->with('error', __('itsme::itsme.errors.security_error'));

        } catch (\Exception $e) {
            Log::error('Itsme callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')
                ->with('error', __('itsme::itsme.errors.unexpected_error'));
        }
    }

    /**
     * Check if user exists.
     */
    protected function userExists(array $userInfo): bool
    {
        return $this->findUser($userInfo) !== null;
    }

    /**
     * Resolve an existing user for the itsme subject.
     *
     * Strictly links by itsme_id. When link_by_email is enabled, an account
     * may be matched by verified email only if both the itsme email claim and
     * the existing account's email_verified_at are verified.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function findUser(array $userInfo)
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        $sub = $userInfo['sub'] ?? null;

        if ($sub !== null && $sub !== '') {
            $user = $userModel::where('itsme_id', $sub)->first();
            if ($user) {
                return $user;
            }
        }

        if (config('itsme.link_by_email', false)) {
            $email = $userInfo['email'] ?? null;
            $emailVerified = ! empty($userInfo['email_verified']);

            if ($emailVerified && is_string($email) && $email !== '') {
                return $userModel::where('email', $email)
                    ->whereNotNull('email_verified_at')
                    ->first();
            }
        }

        return null;
    }

    /**
     * Create or update a user from Itsme user info.
     *
     * @param array $userInfo User information from Itsme
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    protected function createOrUpdateUser(array $userInfo)
    {
        // Get the User model class
        $userModel = config('auth.providers.users.model', \App\Models\User::class);

        $user = $this->findUser($userInfo);

        $userData = [
            'itsme_id' => $userInfo['sub'] ?? null,
            'email' => $userInfo['email'] ?? null,
            'email_verified_at' => isset($userInfo['email_verified']) && $userInfo['email_verified']
                ? now()
                : null,
        ];

        // Map additional fields if they exist in the user model
        if (isset($userInfo['given_name'])) {
            $userData['first_name'] = $userInfo['given_name'];
        }

        if (isset($userInfo['family_name'])) {
            $userData['last_name'] = $userInfo['family_name'];
        }

        if (isset($userInfo['name'])) {
            $userData['name'] = $userInfo['name'];
        } elseif (isset($userInfo['given_name']) && isset($userInfo['family_name'])) {
            $userData['name'] = $userInfo['given_name'] . ' ' . $userInfo['family_name'];
        }

        if (isset($userInfo['phone_number'])) {
            $userData['phone'] = $userInfo['phone_number'];
        }

        // Only write columns the consuming model actually accepts
        $userData = $this->filterFillable($userModel, $userData);

        $user = $user ?? new $userModel();

        if ($user->exists) {
            // Update existing user
            $user->update($userData);
        } else {
            // Create new user
            $user = $userModel::create($userData);
        }

        return $user;
    }

    /**
     * Restrict the data written to the model's fillable columns.
     *
     * @param class-string $userModel
     */
    protected function filterFillable(string $userModel, array $data): array
    {
        $model = new $userModel();
        $fillable = $model->getFillable();
        $guarded = $model->getGuarded();

        // Fully mass-assignable models (guarded = [] or fillable = ['*']) pass through
        if ($fillable === ['*'] || $guarded === []) {
            return $data;
        }

        // Protected model with no explicit fillable: nothing is assignable
        if ($fillable === []) {
            return [];
        }

        return array_intersect_key($data, array_flip($fillable));
    }

    /**
     * Resolve the post-login redirect, refusing cross-origin URLs.
     */
    protected function safeIntendedUrl(): string
    {
        $intended = session()->pull('url.intended', '/');

        if (! is_string($intended) || $intended === '') {
            return '/';
        }

        $host = parse_url($intended, PHP_URL_HOST);

        // Relative path (or bare "/") is safe; anything with a host must be ours
        if ($host === null || $host === false) {
            return $intended;
        }

        $expectedHost = parse_url(config('app.url'), PHP_URL_HOST);

        return $host === $expectedHost ? $intended : '/';
    }
}

