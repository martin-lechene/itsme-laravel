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
    public function redirect()
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
    public function callback(Request $request)
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

            // Log in the user
            Auth::login($user, true);

            // Redirect to intended page or home
            return redirect()->intended('/');

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
        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        
        return $userModel::where('itsme_id', $userInfo['sub'])
            ->orWhere('email', $userInfo['email'] ?? null)
            ->exists();
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

        // Find user by itsme_id or email
        $user = $userModel::where('itsme_id', $userInfo['sub'])
            ->orWhere('email', $userInfo['email'] ?? null)
            ->first();

        $userData = [
            'itsme_id' => $userInfo['sub'],
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

        if ($user) {
            // Update existing user
            $user->update($userData);
        } else {
            // Create new user
            $user = $userModel::create($userData);
        }

        return $user;
    }
}

