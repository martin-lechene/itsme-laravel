<?php

return [
    // Button
    'button_text' => 'Sign in with itsme®',
    
    // Error messages
    'errors' => [
        'title' => 'Authentication Error',
        'redirect_failed' => 'An error occurred while redirecting to Itsme.',
        'session_expired' => 'Session expired. Please try again.',
        'security_error' => 'Security error. Please try again.',
        'unexpected_error' => 'An unexpected error occurred.',
        'authentication_failed' => 'An error occurred during Itsme authentication.',
        'back_to_login' => 'Back to login',
        
        // OAuth errors
        'access_denied' => 'User denied authorization',
        'invalid_request' => 'Invalid request',
        'invalid_client' => 'Invalid client ID or secret',
        'invalid_grant' => 'Authorization code is invalid or expired',
        'unauthorized_client' => 'Client is not authorized',
        'unsupported_response_type' => 'Unsupported response type',
        'invalid_scope' => 'Invalid scope',
        'server_error' => 'Itsme server error',
        'temporarily_unavailable' => 'Service temporarily unavailable',
        'unknown_error' => 'An error occurred during authentication',
        
        // Token errors
        'token_exchange_failed' => 'Token exchange failed',
        'invalid_token_response' => 'Invalid token response',
        'user_info_failed' => 'Failed to retrieve user information',
        'invalid_state' => 'Invalid state parameter',
        'authorization_code_missing' => 'Authorization code missing',
    ],
];
