# 🧠 Memory Game - Version PHP Pure
### 🔧 Technologies utilisées
- **PHP 7.4+** - Logique serveur
- **MySQL** - Base de données relationnelle
- **Sessions PHP** - Gestion d'état
- **Formulaires HTML** - Interactions utilisateur
- **CSS3 pur** - Animations et stylingeu de Memory moderne entièrement développé en **PHP/HTML/CSS** sans JavaScript.

## 🎯 Fonctionnalités

### ✨ Jeu de Memory classique
- **Interface moderne** avec animations CSS pures
- **Difficulté variable** : de 3 à 12 paires (6 à 24 cartes)
- **Système de score** sophistiqué
- **Timer en temps réel** sans JavaScript

### 👥 Système utilisateur complet
- **Inscription/Connexion** simple
- **Profils individuels** avec statistiques
- **Sauvegarde automatique** des scores
- **Classement des 10 meilleurs joueurs**

### 📊 Statistiques et accomplissements
- Historique personnel des parties
- Accomplissements à débloquer
- Position dans le classement global
- Métriques détaillées (temps, coups, scores)

## �️ Architecture - 100% PHP

### ✅ Classe Card.php obligatoire (comme demandé)
```php
class Card {
    private $id;
    private $symbol;
    private $isFlipped;
    private $isMatched;
    // ... méthodes complètes
}
```

### � Technologies utilisées
- **PHP 7.4+** - Logique serveur
- **SQLite** - Base de données
- **Sessions PHP** - Gestion d'état
- **Formulaires HTML** - Interactions utilisateur
- **CSS3 pur** - Animations et styling

### 🏗️ Architecture orientée objet
- `Card.php` - Gestion des cartes
- `Game.php` - Logique de jeu complète
- `Database.php` - Gestion SQLite
- `Player.php` - Gestion des joueurs

## 🚀 Installation

### Prérequis
- Serveur web (Apache/Nginx) avec PHP 7.4+
- **MySQL 5.7+ ou MariaDB 10.3+**
- Extension PDO MySQL activée
- Sessions PHP activées

### Installation simple
1. **Démarrez MySQL** (via WAMP/XAMPP)
2. **Configurez la base** : Accédez à `http://localhost/memory/install.php`
3. **Testez l'installation** : `http://localhost/memory/test.php`
4. **Jouez** : `http://localhost/memory/index.php`

### Configuration manuelle
1. Créez la base `memory_game` dans phpMyAdmin
2. Exécutez le script `config/database.sql`
3. Modifiez `config/DatabaseConfig.php` si nécessaire

## 🎮 Comment jouer

1. **Créez un compte** avec un nom d'utilisateur
2. **Choisissez la difficulté** (3-12 paires)
3. **Cliquez sur "Nouveau Jeu"**
4. **Retournez les cartes** en cliquant sur "?"
5. **Cliquez sur "Continuer"** après avoir retourné 2 cartes
6. **Trouvez toutes les paires** pour gagner !

## ⚡ Fonctionnement sans JavaScript

### 🔄 Gestion des interactions
- **Formulaires HTML** pour chaque action
- **Sessions PHP** pour maintenir l'état du jeu
- **Rechargement automatique** pour le timer (meta refresh)
- **POST/Redirect/GET** pour éviter la resoumission

### 💾 Persistance des données
- **Jeu en cours** sauvé en session
- **Scores** stockés en base MySQL
- **Statistiques** calculées côté serveur
- **Index optimisés** pour performance

### � Interface moderne
- **CSS Grid** pour la disposition des cartes
- **Animations CSS** pour les effets visuels
- **Design responsive** mobile/desktop
- **Gradients et ombres** pour le style moderne

## 🏆 Système de score

```
Score = (Paires × 1000) + Bonus temps - Pénalité coups
- Bonus temps : jusqu'à 3000 points (< 5 minutes)
- Pénalité : -50 points par coup supplémentaire
```

## 📱 Avantages de la version PHP pure

### ✅ Compatibilité maximale
- Fonctionne sur **tous les navigateurs**
- Même les très anciens navigateurs
- Pas de problème de JavaScript désactivé
- Compatible avec les lecteurs d'écran

### 🔒 Sécurité renforcée
- **Logique côté serveur** uniquement
- Impossible de tricher côté client
- Validation serveur de toutes les actions
- Protection CSRF naturelle

### 🚀 Performance
- **Pas de JavaScript** à télécharger
- Chargement plus rapide
- Consommation mémoire réduite
- Fonctionne sur appareils anciens

## 📂 Structure du projet

```
memory/
├── classes/              # Classes PHP (POO)
│   ├── Card.php         # Classe obligatoire des cartes
│   ├── Game.php         # Logique de jeu
│   ├── Database.php     # Gestion MySQL
│   └── Player.php       # Gestion joueurs
├── config/              # Configuration
│   ├── DatabaseConfig.php  # Config MySQL
│   └── database.sql     # Script création base
├── assets/css/          # Styles CSS
├── index.php            # Page principale
├── leaderboard.php      # Classement
├── profile.php          # Profil utilisateur
├── install.php          # Installation guidée
└── test.php             # Page de test
```

## 🎯 Fonctionnalités avancées

### � Statistiques complètes
- Nombre total de parties
- Meilleur score personnel
- Temps et coups moyens
- Classement global

### 🏅 Accomplissements
- Premier Pas (1ère partie)
- Joueur Régulier (10 parties)
- Démon de Vitesse (< 60 secondes)
- Perfectionniste (minimum de coups)
- Et plus...

### 🎨 Interface moderne
- Design gradients et animations CSS
- Cartes avec symboles emoji colorés
- Responsive design
- Messages de feedback

## 🔧 Personnalisation

Modifiez facilement :
- **Symboles des cartes** dans `Game.php`
- **Couleurs** dans `Game.php`
- **Système de score** dans `calculateScore()`
- **Styles** dans `assets/css/style.css`

## � Exemple d'utilisation

```php
// Créer un nouveau jeu
$game = new Game(6); // 6 paires

// Retourner une carte
$game->flipCard(5);

// Obtenir les statistiques
$stats = $game->getStats();
echo "Score: " . $stats['score'];
```

## 🎉 Conclusion

Ce Memory Game démontre qu'il est possible de créer une expérience de jeu moderne et engageante en utilisant uniquement **PHP, HTML et CSS**. 

**Avantages clés :**
- ✅ Respect total des consignes (classe Card.php obligatoire)
- ✅ Fonctionnalités complètes (classements, profils, accomplissements)
- ✅ Compatible avec tous les navigateurs
- ✅ Sécurisé et performant
- ✅ Code propre et extensible

**Parfait pour apprendre la POO en PHP tout en s'amusant !** 🎮

---

**Testez maintenant :** `http://localhost/memory/test.php`# memory
