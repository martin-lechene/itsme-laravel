# Politique de Sécurité

## 🔒 Support des Versions

Nous fournissons des mises à jour de sécurité pour les versions suivantes :

| Version | Supporté          |
| ------- | ----------------- |
| 1.x     | :white_check_mark: |

## 🚨 Signaler une Vulnérabilité

Si vous découvrez une vulnérabilité de sécurité, **NE CRÉEZ PAS D'ISSUE PUBLIQUE**.

Veuillez envoyer un email à : **martin.lechene@example.com**

### Informations à Inclure

- Type de vulnérabilité
- Composant affecté
- Étapes pour reproduire
- Impact potentiel
- Suggestions de correction (si vous en avez)

### Processus

1. Nous accuserons réception dans les 48 heures
2. Nous confirmerons le problème dans les 7 jours
3. Nous publierons un correctif dès que possible
4. Nous créditerons le découvreur (s'il le souhaite)

## 🛡️ Bonnes Pratiques de Sécurité

### Pour les Utilisateurs

1. **Toujours utiliser HTTPS** en production
2. **Valider les redirect URIs** dans le portail Itsme
3. **Utiliser PKCE** (activé par défaut)
4. **Vérifier les tokens** (activé par défaut)
5. **Garder le package à jour**

### Configuration Recommandée

```env
ITSME_USE_PKCE=true
ITSME_VERIFY_TOKEN=true
ITSME_ENVIRONMENT=production
```

### Variables d'Environnement Sécurisées

- Ne jamais commiter `.env` dans le repository
- Utiliser des secrets sécurisés pour `ITSME_CLIENT_SECRET`
- Rotater régulièrement les credentials

## 🔐 Mesures de Sécurité Implémentées

### Protection CSRF
- Utilisation du paramètre `state` pour protéger contre les attaques CSRF
- Validation stricte du state lors du callback

### Protection Replay Attack
- Utilisation du `nonce` pour empêcher la réutilisation de tokens
- Validation du nonce dans le ID token

### PKCE (Proof Key for Code Exchange)
- Protection contre l'interception du code d'autorisation
- Génération sécurisée de code verifier et challenge

### Validation des Tokens
- Vérification de la signature JWT (si activée)
- Validation de l'expiration
- Vérification de l'audience (client_id)
- Vérification de l'issuer

### Gestion des Sessions
- Nettoyage automatique des données sensibles après authentification
- Stockage sécurisé des valeurs temporaires

## 📝 Historique des Vulnérabilités

Aucune vulnérabilité connue pour le moment.

## 🙏 Remerciements

Merci de nous aider à maintenir ce package sécurisé !

