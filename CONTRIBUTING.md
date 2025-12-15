# Guide de Contribution

Merci de votre intérêt pour contribuer au package Itsme Laravel ! 🎉

## 📋 Table des Matières

- [Code de Conduite](#code-de-conduite)
- [Comment Contribuer](#comment-contribuer)
- [Processus de Développement](#processus-de-développement)
- [Standards de Code](#standards-de-code)
- [Tests](#tests)
- [Documentation](#documentation)

## Code de Conduite

En participant à ce projet, vous acceptez de respecter notre code de conduite. Soyez respectueux et professionnel dans toutes vos interactions.

## Comment Contribuer

### Signaler un Bug

Si vous trouvez un bug, veuillez créer une issue avec :
- Une description claire du problème
- Les étapes pour reproduire le bug
- Le comportement attendu
- Votre environnement (PHP, Laravel, version du package)
- Des captures d'écran si applicable

### Proposer une Fonctionnalité

Les suggestions de fonctionnalités sont les bienvenues ! Créez une issue pour discuter de votre idée avant de commencer le développement.

### Soumettre une Pull Request

1. Fork le projet
2. Créez une branche pour votre fonctionnalité (`git checkout -b feature/ma-fonctionnalite`)
3. Committez vos changements (`git commit -m 'Ajout de ma fonctionnalité'`)
4. Poussez vers la branche (`git push origin feature/ma-fonctionnalite`)
5. Ouvrez une Pull Request

## Processus de Développement

### Configuration de l'Environnement

```bash
# Cloner le repository
git clone https://github.com/martin-lechene/itsme-laravel.git
cd itsme-laravel

# Installer les dépendances
composer install

# Copier les fichiers de configuration
cp .env.example .env
```

### Structure du Projet

- `src/` - Code source du package
- `tests/` - Tests unitaires et fonctionnels
- `config/` - Fichiers de configuration
- `resources/` - Vues et assets
- `routes/` - Routes du package

## Standards de Code

### PHP

- Suivre les standards PSR-12
- Utiliser des types stricts (`declare(strict_types=1);`)
- Documenter toutes les méthodes publiques avec PHPDoc
- Respecter les conventions de nommage Laravel

### Formatage

Le projet utilise PHP CS Fixer pour le formatage automatique :

```bash
composer format
```

### Analyse Statique

PHPStan est utilisé pour l'analyse statique :

```bash
composer analyse
```

## Tests

### Exécuter les Tests

```bash
# Tous les tests
composer test

# Tests unitaires uniquement
vendor/bin/phpunit tests/Unit

# Tests fonctionnels uniquement
vendor/bin/phpunit tests/Feature

# Avec couverture de code
composer test-coverage
```

### Écrire des Tests

- Chaque nouvelle fonctionnalité doit être accompagnée de tests
- Les tests doivent être clairs et bien nommés
- Utiliser des mocks pour les dépendances externes
- Couvrir les cas d'erreur et les cas limites

### Exemple de Test

```php
<?php

namespace ItsmeLaravel\Itsme\Tests\Unit;

use ItsmeLaravel\Itsme\Tests\TestCase;

class MyFeatureTest extends TestCase
{
    public function test_something_works(): void
    {
        // Arrange
        $input = 'test';
        
        // Act
        $result = doSomething($input);
        
        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

## Documentation

### PHPDoc

Toutes les méthodes publiques doivent avoir une documentation PHPDoc complète :

```php
/**
 * Description de la méthode.
 *
 * @param string $param Description du paramètre
 * @return array Description de la valeur de retour
 * @throws \Exception Description de l'exception
 */
public function myMethod(string $param): array
{
    // ...
}
```

### README

- Mettre à jour le README.md pour les nouvelles fonctionnalités
- Ajouter des exemples d'utilisation
- Documenter les breaking changes dans le CHANGELOG.md

## Checklist avant de Soumettre

- [ ] Les tests passent (`composer test`)
- [ ] Le code respecte les standards (`composer format`)
- [ ] L'analyse statique passe (`composer analyse`)
- [ ] La documentation est à jour
- [ ] Le CHANGELOG.md est mis à jour
- [ ] Les commits sont clairs et descriptifs

## Questions ?

Si vous avez des questions, n'hésitez pas à ouvrir une issue ou à contacter les mainteneurs.

Merci pour votre contribution ! 🙏

