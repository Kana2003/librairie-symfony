# Projet E-commerce - Livres Informatiques

## Description du projet

Ce projet est un site e-commerce développé en Symfony pour la vente de livres informatiques. Il respecte les consignes du TP2 et du Projet Final de Programmation Web - Été 2025.

## 🚀 Test du site

### Accès au site

-   **URL locale** : http://localhost:8000
-   **Serveur de développement** : Démarrez avec `php -S localhost:8000 -t public`

### Comptes de test

-   **Administrateur** :
    -   Email : `admin@ecommerce.com`
    -   Mot de passe : `admin123`
    -   Accès : Gestion des livres, toutes les fonctionnalités admin

### Fonctionnalités testées

✅ **Page d'accueil** : Affichage des 22 livres avec images  
✅ **Recherche** : Recherche par titre, auteur, description  
✅ **Connexion/Inscription** : Formulaires fonctionnels  
✅ **Gestion des livres** : CRUD complet pour les administrateurs  
✅ **Panier** : Ajout, modification, suppression d'articles  
✅ **Images** : Utilisation des images fournies dans `Livres-Informatiques`  
✅ **Base de données** : Connexion MySQL et données chargées

## Fonctionnalités principales

### Pour les utilisateurs (clients)

-   Inscription et connexion avec authentification
-   Recherche de livres informatiques
-   Ajout de livres au panier
-   Gestion du panier (modification des quantités, suppression)
-   Achat direct ou via panier
-   Paiement via PayPal
-   Historique des commandes
-   Gestion du profil utilisateur
-   Déconnexion automatique après 2 minutes d'inactivité

### Pour les administrateurs

-   Gestion complète des livres (ajout, modification, suppression)
-   Recherche de livres
-   Gestion des stocks
-   Visualisation des commandes

## Technologies utilisées

-   **Backend**: Symfony 7.x, PHP 8.x
-   **Base de données**: MySQL
-   **Frontend**: Bootstrap 5, Twig
-   **Paiement**: PayPal
-   **Email**: Mailtrap.io pour les tests OTP

## Installation et configuration

### Prérequis

-   PHP 8.1 ou supérieur
-   Composer
-   MySQL
-   Node.js et npm

### Étapes d'installation

1. **Cloner le projet**

```bash
git clone [url-du-repo]
cd ecommerce
```

2. **Installer les dépendances PHP**

```bash
composer install
```

3. **Installer les dépendances JavaScript**

```bash
npm install
```

4. **Configurer la base de données**

```bash
# Créer la base de données
php bin/console doctrine:database:create

# Créer les migrations
php bin/console make:migration

# Exécuter les migrations
php bin/console doctrine:migrations:migrate
```

5. **Compiler les assets**

```bash
npm run dev
```

6. **Configurer les paramètres**

-   Copier le fichier `.env.local` depuis `.env`
-   Configurer les paramètres de base de données
-   Configurer les paramètres PayPal
-   Configurer les paramètres Mailtrap.io

7. **Charger les données initiales**

```bash
# Charger les livres
php bin/console app:load-books

# Créer un administrateur
php bin/console app:create-admin
```

8. **Démarrer le serveur**

```bash
php -S localhost:8000 -t public
```

## Structure du projet

### Entités principales

-   `User`: Utilisateurs du système
-   `Book`: Livres informatiques
-   `Cart`: Panier d'achat
-   `CartItem`: Éléments du panier
-   `Order`: Commandes
-   `OrderItem`: Éléments de commande

### Contrôleurs

-   `HomeController`: Page d'accueil et recherche
-   `SecurityController`: Authentification et inscription
-   `AdminBookController`: Gestion des livres (admin)
-   `CartController`: Gestion du panier
-   `OrderController`: Gestion des commandes
-   `UserController`: Gestion du profil utilisateur

### Formulaires

-   `UserRegistrationType`: Inscription utilisateur
-   `LoginFormType`: Connexion
-   `BookType`: Gestion des livres
-   `UserProfileType`: Modification du profil

## Commandes Symfony utilisées

### Création du projet

```bash
composer create-project symfony/skeleton ecommerce
cd ecommerce
composer require webapp
```

### Installation des packages

```bash
composer require dompdf/dompdf
composer require symfony/webpack-encore-bundle
```

### Création des entités

```bash
php bin/console make:user
php bin/console make:entity Book
php bin/console make:entity Cart
php bin/console make:entity CartItem
php bin/console make:entity Order
php bin/console make:entity OrderItem
```

### Création des formulaires

```bash
php bin/console make:form UserRegistrationType User
php bin/console make:form LoginFormType
php bin/console make:form BookType Book
php bin/console make:form UserProfileType User
```

### Création des contrôleurs

```bash
php bin/console make:controller HomeController
php bin/console make:controller SecurityController
php bin/console make:controller AdminBookController
php bin/console make:controller CartController
```

### Configuration de la base de données

```bash
php bin/console doctrine:database:create
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### Installation de Bootstrap

```bash
npm install bootstrap @popperjs/core
npm run dev
```

### Commandes personnalisées

```bash
# Charger les livres dans la base de données
php bin/console app:load-books

# Créer un administrateur
php bin/console app:create-admin
```

## Configuration de la sécurité

### Firewall

Le système utilise le firewall Symfony avec les routes suivantes :

-   `/login`: Page de connexion
-   `/register`: Page d'inscription
-   `/admin/*`: Zone administrateur (ROLE_ADMIN)
-   `/cart/*`: Zone panier (ROLE_USER)

### Authentification

-   Authentification par email/mot de passe
-   Hachage des mots de passe avec bcrypt
-   Session avec timeout de 2 minutes

## Fonctionnalités avancées

### Gestion des stocks

-   Mise à jour automatique du statut des livres
-   Vérification de disponibilité lors de l'ajout au panier
-   Gestion des conflits d'achat simultané

### Taxes

-   Calcul automatique des taxes du Québec (GST 5% + QST 9.975%)
-   Affichage des prix avec et sans taxes

### Responsive Design

-   Interface adaptative avec Bootstrap 5
-   Compatible mobile et desktop

### Images des livres

-   Utilisation des images fournies dans le dossier `Livres-Informatiques`
-   Gestion des erreurs d'affichage avec image de fallback
-   Optimisation des images pour le web

## Tests

Pour exécuter les tests :

```bash
php bin/phpunit
```

## Déploiement

1. Configurer l'environnement de production
2. Optimiser les assets : `npm run build`
3. Vider le cache : `php bin/console cache:clear --env=prod`
4. Configurer le serveur web (Apache/Nginx)

## Auteur

Thierno Oumar Kana Diallo -

## Licence

Ce projet est développé dans le cadre du cours de Programmation Web - CE2.
