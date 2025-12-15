<?php

namespace ItsmeLaravel\Itsme\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
                ->with('error', 'Une erreur est survenue lors de la redirection vers Itsme.');
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
            $user = $this->createOrUpdateUser($userInfo);

            // Log in the user
            Auth::login($user, true);

            // Redirect to intended page or home
            return redirect()->intended('/');

        } catch (InvalidStateException $e) {
            Log::warning('Itsme invalid state', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Session expirée. Veuillez réessayer.');

        } catch (AuthenticationFailedException $e) {
            Log::error('Itsme authentication failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->with('error', $e->getMessage());

        } catch (InvalidTokenException $e) {
            Log::error('Itsme invalid token', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Erreur de sécurité. Veuillez réessayer.');

        } catch (\Exception $e) {
            Log::error('Itsme callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Une erreur inattendue est survenue.');
        }
    }

    /**
     * Create or update a user from Itsme user info.
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

