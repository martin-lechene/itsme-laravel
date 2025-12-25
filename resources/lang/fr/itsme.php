<?php

return [
    // Button
    'button_text' => 'Se connecter avec itsme®',
    
    // Error messages
    'errors' => [
        'title' => 'Erreur d\'authentification',
        'redirect_failed' => 'Une erreur est survenue lors de la redirection vers Itsme.',
        'session_expired' => 'Session expirée. Veuillez réessayer.',
        'security_error' => 'Erreur de sécurité. Veuillez réessayer.',
        'unexpected_error' => 'Une erreur inattendue est survenue.',
        'authentication_failed' => 'Une erreur est survenue lors de l\'authentification Itsme.',
        'back_to_login' => 'Retour à la connexion',
        
        // OAuth errors
        'access_denied' => 'L\'utilisateur a refusé l\'autorisation',
        'invalid_request' => 'La requête est invalide',
        'invalid_client' => 'Client ID ou secret invalide',
        'invalid_grant' => 'Le code d\'autorisation est invalide ou expiré',
        'unauthorized_client' => 'Le client n\'est pas autorisé',
        'unsupported_response_type' => 'Type de réponse non supporté',
        'invalid_scope' => 'Scope invalide',
        'server_error' => 'Erreur serveur Itsme',
        'temporarily_unavailable' => 'Service temporairement indisponible',
        'unknown_error' => 'Une erreur est survenue lors de l\'authentification',
        
        // Token errors
        'token_exchange_failed' => 'Échec de l\'échange de token',
        'invalid_token_response' => 'Réponse de token invalide',
        'user_info_failed' => 'Échec de la récupération des informations utilisateur',
        'invalid_state' => 'Paramètre state invalide',
        'authorization_code_missing' => 'Code d\'autorisation manquant',
    ],
];
