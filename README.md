# Librairie Symfony — Plateforme e-commerce de livres informatiques

> Projet de fin de session | DEC Techniques de l'informatique | Institut Teccart, Montréal

![Symfony](https://img.shields.io/badge/Symfony-7.3-black?logo=symfony)
![PHP](https://img.shields.io/badge/PHP-8.2+-8892BF?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)

Application web e-commerce permettant la vente de livres informatiques avec authentification, panier, paiement PayPal (sandbox), taxes québécoises et back-office administrateur.

---

## Aperçu

| Page | Capture |
|------|---------|
| Accueil — catalogue | ![Accueil](docs/screenshots/01-accueil.png) |
| Détail d'un livre | ![Détail livre](docs/screenshots/02-detail-livre.png) |
| Panier | ![Panier](docs/screenshots/03-panier.png) |
| Paiement PayPal | ![Paiement](docs/screenshots/04-paiement.png) |
| Historique des commandes | ![Commandes](docs/screenshots/05-mes-commandes.png) |
| Back-office admin | ![Admin](docs/screenshots/06-admin.png) |

> Captures d'écran de l'application en environnement local.

---

## Fonctionnalités

### Catalogue et recherche
- Affichage de tous les livres avec titre, auteur, prix TTC et statut de stock
- Recherche par titre, auteur ou description (HomeController)
- Statut automatique : « En stock », « Seulement N en stock », « En rupture de stock »

### Authentification
- Inscription et connexion par email / mot de passe
- Connexion alternative par **code OTP envoyé par email** (flux indépendant, 6 chiffres, expiration 10 min)
- Déconnexion automatique après **2 minutes d'inactivité** (SessionTimeoutListener)
- Suppression de compte (soft delete — isDeleted / isActive)

### Panier
- Panier par utilisateur connecté (un panier actif par compte)
- Ajout, modification de quantité, suppression d'articles, vidage complet
- **Panier invité** : ajout possible avant connexion (stockage en session)
- Calcul des sous-totaux et du total TTC en temps réel

### Commandes et paiement
- Conversion du panier en commande lors du checkout
- Intégration **PayPal sandbox** : génération d'une URL de paiement directe (`_xclick`, devise CAD)  
  *(Note : les credentials sont des valeurs de test ; le succès est confirmé localement sans vérification IPN)*
- Statuts de commande : `pending` → `paid` → `delivered`
- Historique des commandes accessible depuis le profil
- **Téléchargement de facture PDF** par commande (DomPDF)

### Taxes québécoises
- TPS : 5 % | TVQ : 9,975 % | **Total : 14,975 %**
- Calculées automatiquement dans les entités Book, CartItem, OrderItem et Order

### Profil utilisateur
- Modification des informations personnelles (téléphone, adresse, ville, code postal, province)
- Changement de mot de passe (vérification de l'ancien)

### Back-office administrateur (ROLE_ADMIN)
- **CRUD complet des livres** : création, lecture, modification, suppression
- Upload d'image de couverture
- Recherche dans le catalogue
- Mise à jour automatique du stock lors d'une modification (événement `BookUpdateEvent`)
- Notifications aux utilisateurs dont les livres sont dans le panier si le prix ou le stock change

---

## Stack technique

| Composant | Version |
|-----------|---------|
| PHP | >= 8.2 |
| Symfony | 7.3.* |
| Doctrine ORM | ^3.5 |
| Doctrine DBAL | ^3 |
| DomPDF | ^3.1 |
| Symfony Mailer | 7.3.* |
| PHPUnit | ^11.5 |
| Bootstrap | ^5.3.7 |
| Webpack Encore | ^5.0.0 |
| Node.js | >= 18 (pour la compilation des assets) |
| Base de données | MySQL 8.0 |

---

## Installation

### Prérequis

- PHP >= 8.2 avec extensions `ctype`, `iconv`
- Composer
- MySQL 8.0
- Node.js >= 18 et npm
- Serveur local (XAMPP, Laragon, Symfony CLI…)

### Étapes

**1. Cloner le dépôt**

```bash
git clone https://github.com/kana-di/librairie-symfony.git
cd librairie-symfony
```

**2. Installer les dépendances PHP**

```bash
composer install
```

**3. Configurer l'environnement**

```bash
cp .env .env.local
```

Éditer `.env.local` :

```dotenv
# Base de données
DATABASE_URL="mysql://root:@127.0.0.1:3306/librairie_symfony?serverVersion=8.0"

# Email (Mailtrap pour les tests OTP)
MAILER_DSN=smtp://username:password@sandbox.smtp.mailtrap.io:2525
```

**4. Créer la base de données et exécuter les migrations**

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

**5. Charger les données de démonstration**

```bash
# Charger le catalogue de livres
php bin/console app:load-books

# Créer le compte administrateur
php bin/console app:create-admin
```

**6. Compiler les assets CSS/JS**

```bash
npm install
npm run dev
```

**7. Démarrer le serveur**

```bash
# Avec le CLI Symfony (recommandé)
symfony server:start

# Ou avec PHP
php -S localhost:8000 -t public
```

Ouvrir [http://localhost:8000](http://localhost:8000)

---

## Comptes de test

Le compte administrateur est créé automatiquement par la commande
`php bin/console app:create-admin` lors de l'installation. Les identifiants
sont à configurer dans cette commande (`src/Command/CreateAdminCommand.php`)
ou directement en base de données.

Pour créer un compte utilisateur standard, utiliser le formulaire d'inscription
sur `/register`. La connexion OTP fonctionne avec n'importe quel compte actif
si un serveur SMTP est configuré (Mailtrap recommandé pour le développement).

---

## Architecture

### Entités (src/Entity/)

| Entité | Rôle principal |
|--------|----------------|
| `User` | Compte utilisateur, rôles, soft delete |
| `Book` | Livre avec prix, stock, statut automatique |
| `Cart` | Panier actif par utilisateur |
| `CartItem` | Ligne de panier (livre + quantité + prix) |
| `Order` | Commande avec numéro unique, statut et total TTC |
| `OrderItem` | Ligne de commande (snapshot du prix au moment de l'achat) |

### Contrôleurs (src/Controller/)

| Contrôleur | Routes | Accès |
|-----------|--------|-------|
| `HomeController` | `GET /` | Public |
| `SecurityController` | `/login`, `/register`, `/logout` | Public |
| `TwoFactorController` | `/2fa/request`, `/2fa/verify`, `/2fa/resend` | Public |
| `CartController` | `/cart/*` | ROLE_USER |
| `PaymentController` | `/payment/*` | ROLE_USER |
| `UserController` | `/user/profile`, `/user/orders`, `/user/download-invoice/{orderNumber}` | ROLE_USER |
| `DirectPurchaseController` | `/buy/*`, `/guest-cart/*` | Public / ROLE_USER |
| `AdminBookController` | `/admin/book/*` | ROLE_ADMIN |
| `NotificationController` | `/notifications/*` | ROLE_USER |

### Services (src/Service/)

| Service | Rôle |
|---------|------|
| `OtpService` | Génération et envoi d'OTP par email |
| `PayPalService` | Création d'URL de paiement PayPal sandbox |
| `PdfService` | Génération de factures PDF avec DomPDF |
| `NotificationService` | Notifications en session pour les utilisateurs |

---

## Ce que ce projet m'a appris

- **Architecture Symfony MVC** : séparation contrôleurs / services / entités, injection de dépendances
- **Doctrine ORM** : modélisation relationnelle, migrations, requêtes personnalisées avec QueryBuilder
- **Sécurité Symfony** : firewalls, voters, `IsGranted`, hachage de mots de passe, CSRF
- **Système d'événements** : `EventDispatcher`, création d'événements personnalisés (`BookUpdateEvent`) et listeners
- **Envoi d'emails** : Symfony Mailer, templates Twig pour les emails HTML
- **Génération de PDF** : intégration de DomPDF dans un service Symfony
- **Frontend** : Webpack Encore, Bootstrap 5, templates Twig avec héritage
- **Flux de paiement** : modélisation d'un cycle commande → paiement → confirmation
- **Taxes** : application des règles fiscales québécoises (TPS + TVQ) à tous les niveaux du modèle
- **Soft delete** : désactivation logique des comptes sans suppression physique

---

## Auteur

**Kana Diallo**  
Étudiant — DEC Techniques de l'informatique  
Institut Teccart, Montréal  
[kanadiallo20@gmail.com](mailto:kanadiallo20@gmail.com)  
[github.com/kana-di](https://github.com/kana-di)

---

*Projet académique réalisé dans le cadre du DEC en Techniques de l'informatique à l'Institut Teccart (Montréal).*
