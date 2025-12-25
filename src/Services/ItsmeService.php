<?php

namespace ItsmeLaravel\Itsme\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ItsmeLaravel\Itsme\Exceptions\AuthenticationFailedException;
use ItsmeLaravel\Itsme\Exceptions\InvalidStateException;
use ItsmeLaravel\Itsme\Exceptions\InvalidTokenException;

class ItsmeService
{
    public function __construct(
        protected TokenValidator $tokenValidator,
        protected OpenIdDiscovery $discovery
    ) {
    }

    /**
     * Get the authorization URL for redirecting to Itsme.
     *
     * This method generates a secure authorization URL with state, nonce, and PKCE parameters.
     * The state and nonce are stored in the session for validation during the callback.
     *
     * @return string The authorization URL to redirect the user to
     * @throws \ItsmeLaravel\Itsme\Exceptions\ItsmeException If discovery fails
     */
    public function getAuthorizationUrl(): string
    {
        $state = $this->generateState();
        $nonce = $this->generateNonce();

        // Store in session
        session()->put('itsme.state', $state);
        session()->put('itsme.nonce', $nonce);

        $params = [
            'response_type' => 'code',
            'client_id' => config('itsme.client_id'),
            'redirect_uri' => $this->getRedirectUri(),
            'scope' => implode(' ', config('itsme.scopes', [])),
            'state' => $state,
            'nonce' => $nonce,
        ];

        // Add PKCE if enabled
        if (config('itsme.use_pkce', true)) {
            $codeVerifier = $this->generateCodeVerifier();
            $codeChallenge = $this->generateCodeChallenge($codeVerifier);
            
            session()->put('itsme.code_verifier', $codeVerifier);
            
            $params['code_challenge'] = $codeChallenge;
            $params['code_challenge_method'] = 'S256';
        }

        $authorizationEndpoint = $this->discovery->getAuthorizationEndpoint();

        return $authorizationEndpoint . '?' . http_build_query($params);
    }

    /**
     * Handle the callback from Itsme.
     *
     * This method processes the authorization callback, validates the state parameter,
     * exchanges the authorization code for tokens, validates the ID token, and retrieves
     * user information from the UserInfo endpoint.
     *
     * @param Request $request The HTTP request containing the callback parameters
     * @return array User information from Itsme (sub, email, name, etc.)
     * @throws InvalidStateException If the state parameter is invalid
     * @throws AuthenticationFailedException If authentication fails
     * @throws InvalidTokenException If token validation fails
     */
    public function handleCallback(Request $request): array
    {
        // Check for errors
        if ($error = $request->get('error')) {
            $errorDescription = $request->get('error_description');
            $this->handleError($error, $errorDescription);
        }

        // Verify state
        $state = $request->get('state');
        $sessionState = session()->get('itsme.state');

        if (!$state || $state !== $sessionState) {
            throw new InvalidStateException(__('itsme::itsme.errors.invalid_state'));
        }

        // Get authorization code
        $code = $request->get('code');
        if (!$code) {
            throw new AuthenticationFailedException(__('itsme::itsme.errors.authorization_code_missing'));
        }

        // Exchange code for token
        $tokens = $this->exchangeCodeForToken($code);

        // Validate ID token
        $nonce = session()->get('itsme.nonce');
        $idTokenData = $this->tokenValidator->validateIdToken($tokens['id_token'], $nonce);

        // Get user info
        $userInfo = $this->getUserInfo($tokens['access_token']);

        // Merge ID token claims with user info
        $userInfo = array_merge($idTokenData, $userInfo);

        // Clean up session
        session()->forget(['itsme.state', 'itsme.nonce', 'itsme.code_verifier']);

        return $userInfo;
    }

    /**
     * Exchange authorization code for access token.
     */
    protected function exchangeCodeForToken(string $code): array
    {
        $data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->getRedirectUri(),
            'client_id' => config('itsme.client_id'),
            'client_secret' => config('itsme.client_secret'),
        ];

        // Add PKCE code verifier if used
        if (config('itsme.use_pkce', true) && session()->has('itsme.code_verifier')) {
            $data['code_verifier'] = session()->get('itsme.code_verifier');
        }

        $tokenEndpoint = $this->discovery->getTokenEndpoint();

        $response = Http::asForm()->timeout(30)->post($tokenEndpoint, $data);

        if (!$response->successful()) {
            $error = $response->json('error', 'unknown_error');
            $errorDescription = $response->json('error_description', __('itsme::itsme.errors.token_exchange_failed'));
            
            Log::error('Itsme token exchange failed', [
                'error' => $error,
                'description' => $errorDescription,
                'status' => $response->status(),
            ]);

            throw new AuthenticationFailedException(__('itsme::itsme.errors.token_exchange_failed') . ': ' . $errorDescription);
        }

        $tokens = $response->json();

        if (!isset($tokens['access_token']) || !isset($tokens['id_token'])) {
            throw new AuthenticationFailedException(__('itsme::itsme.errors.invalid_token_response'));
        }

        return $tokens;
    }

    /**
     * Get user information from UserInfo endpoint.
     */
    protected function getUserInfo(string $accessToken): array
    {
        $userInfoEndpoint = $this->discovery->getUserInfoEndpoint();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/json',
        ])->timeout(30)->get($userInfoEndpoint);

        if (!$response->successful()) {
            Log::error('Itsme UserInfo request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new AuthenticationFailedException(__('itsme::itsme.errors.user_info_failed'));
        }

        return $response->json();
    }

    /**
     * Handle authentication errors.
     */
    protected function handleError(string $error, ?string $errorDescription = null): void
    {
        $errorMessages = [
            'access_denied' => __('itsme::itsme.errors.access_denied'),
            'invalid_request' => __('itsme::itsme.errors.invalid_request'),
            'invalid_client' => __('itsme::itsme.errors.invalid_client'),
            'invalid_grant' => __('itsme::itsme.errors.invalid_grant'),
            'unauthorized_client' => __('itsme::itsme.errors.unauthorized_client'),
            'unsupported_response_type' => __('itsme::itsme.errors.unsupported_response_type'),
            'invalid_scope' => __('itsme::itsme.errors.invalid_scope'),
            'server_error' => __('itsme::itsme.errors.server_error'),
            'temporarily_unavailable' => __('itsme::itsme.errors.temporarily_unavailable'),
        ];

        $message = $errorMessages[$error] ?? __('itsme::itsme.errors.unknown_error');

        if ($errorDescription) {
            $message .= ': ' . $errorDescription;
        }

        Log::error('Itsme authentication error', [
            'error' => $error,
            'description' => $errorDescription,
        ]);

        throw new AuthenticationFailedException($message);
    }

    /**
     * Generate a random state value.
     */
    protected function generateState(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Generate a random nonce value.
     */
    protected function generateNonce(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Generate a code verifier for PKCE.
     */
    protected function generateCodeVerifier(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Generate a code challenge from a code verifier.
     */
    protected function generateCodeChallenge(string $verifier): string
    {
        return $this->base64UrlEncode(hash('sha256', $verifier, true));
    }

    /**
     * Base64 URL encode.
     */
    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Get the redirect URI.
     */
    protected function getRedirectUri(): string
    {
        $redirect = config('itsme.redirect', '/itsme/callback');
        
        // If relative, make it absolute
        if (!filter_var($redirect, FILTER_VALIDATE_URL)) {
            $redirect = url($redirect);
        }

        return $redirect;
    }
}

