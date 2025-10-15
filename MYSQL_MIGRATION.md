# 🗄️ Migration vers Base de Données MySQL

## 📋 Changements effectués

### ✅ **Remplacement SQLite → MySQL**

#### **Avant (SQLite)**
```php
// Connexion SQLite
$this->pdo = new PDO('sqlite:' . __DIR__ . '/../data/memory_game.db');
```

#### **Après (MySQL)**
```php
// Connexion MySQL configurée
$this->pdo = new PDO(
    DatabaseConfig::getDSN(),
    DatabaseConfig::DB_USER,
    DatabaseConfig::DB_PASS,
    DatabaseConfig::getPDOOptions()
);
```

### 🔧 **Nouveaux fichiers créés**

1. **`config/DatabaseConfig.php`** - Configuration MySQL centralisée
2. **`config/database.sql`** - Script de création de base
3. **`install.php`** - Interface d'installation guidée

### 📊 **Améliorations de la base de données**

#### **Structure optimisée**
```sql
-- Index pour performance
INDEX idx_username (username)
INDEX idx_score (score DESC)  
INDEX idx_created_at (created_at DESC)

-- Contraintes de clés étrangères
FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE

-- Encodage UTF8MB4 pour émojis
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
```

#### **Requêtes optimisées**
- **INNER JOIN** au lieu de JOIN simple
- **Calculs arrondis** pour les moyennes
- **Gestion des erreurs** améliorée
- **Ranking plus précis**

## 🚀 **Installation MySQL**

### **Étape 1 : Démarrer MySQL**
```bash
# Avec WAMP
Icône WAMP → Restart All Services

# Avec XAMPP  
Panneau XAMPP → Start MySQL
```

### **Étape 2 : Créer la base**
1. Aller sur http://localhost/phpmyadmin
2. Créer base `memory_game`
3. Exécuter le script `config/database.sql`

### **Étape 3 : Configuration**
Modifier `config/DatabaseConfig.php` si nécessaire :
```php
const DB_HOST = 'localhost';
const DB_NAME = 'memory_game';
const DB_USER = 'root';
const DB_PASS = '';  // Vide pour WAMP par défaut
```

### **Étape 4 : Test**
- Aller sur `install.php` pour l'installation guidée
- Ou `test.php` pour les tests techniques

## 💡 **Avantages de MySQL**

### ⚡ **Performance**
- **Index optimisés** pour les requêtes fréquentes
- **Moteur InnoDB** avec transactions ACID
- **Cache de requêtes** intégré
- **Meilleure gestion** de la concurrence

### 🔒 **Robustesse**
- **Contraintes référentielles** strictes
- **Transactions** pour cohérence des données
- **Réplication** possible pour backup
- **Logs détaillés** pour debug

### 🛠️ **Administration**
- **phpMyAdmin** pour interface graphique
- **Export/Import** faciles
- **Backup automatisé** possible
- **Monitoring** des performances

### 📈 **Scalabilité**
- **Millions d'enregistrements** supportés
- **Utilisateurs simultanés** gérés
- **Sharding** possible si nécessaire
- **Cluster** pour haute disponibilité

## 🎯 **Code adapté pour MySQL**

### **Gestion des erreurs**
```php
// Détection username dupliqué
if ($e->getCode() == 23000) {
    return false; // Username déjà existant
}
```

### **Requêtes optimisées**
```php
// Ranking avec sous-requête optimisée
SELECT COUNT(DISTINCT p2.id) + 1 as ranking
FROM players p1
LEFT JOIN scores s1 ON p1.id = s1.player_id
// ... logique complexe mais performante
```

### **Configuration centralisée**
```php
// Paramètres PDO optimaux pour MySQL
PDO::ATTR_EMULATE_PREPARES => false,
PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
```

## 📋 **Données de test incluses**

Le script SQL crée automatiquement :
- **3 joueurs de test** avec scores variés
- **6 parties d'exemple** pour tester le classement
- **Index et contraintes** optimisés

## 🔧 **Maintenance**

### **Vider les données**
```php
$db->clearAllData(); // Remet à zéro toutes les tables
```

### **Statistiques globales**
```php
$stats = $db->getGlobalStats();
// Retourne : total_players, total_games, highest_score, avg_score
```

### **Backup**
- Via phpMyAdmin → Exporter
- Ou ligne de commande : `mysqldump memory_game > backup.sql`

## 🎮 **Impact sur le jeu**

### ✅ **Aucun changement visible**
- Interface identique
- Fonctionnalités inchangées
- Performance améliorée
- Plus stable et robuste

### 🚀 **Prêt pour production**
- Base professionnelle
- Backup facile
- Monitoring possible
- Scalable pour croissance

---

**Le jeu Memory fonctionne maintenant avec une vraie base de données MySQL ! 🎉**