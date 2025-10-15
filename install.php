<!-- <!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Base de Données - Memory Game</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
        }
        .step {
            background: white;
            padding: 25px;
            margin: 20px 0;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 5px solid #667eea;
        }
        .step h3 {
            color: #333;
            margin-bottom: 15px;
        }
        .success {
            color: #27ae60;
            font-weight: bold;
        }
        .error {
            color: #e74c3c;
            font-weight: bold;
        }
        .warning {
            color: #f39c12;
            font-weight: bold;
        }
        .code {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            font-family: monospace;
            margin: 10px 0;
            white-space: pre-wrap;
        }
        .header {
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .nav-links {
            text-align: center;
            margin: 30px 0;
        }
        .nav-links a {
            display: inline-block;
            margin: 0 10px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .nav-links a:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🗄️ Installation Base de Données MySQL</h1>
        <p>Configuration de la base de données pour Memory Game</p>
    </div>

    <div class="step">
        <h3>📋 Étape 1 : Vérification des prérequis</h3>
        
        <?php
        // Vérifier si MySQL est disponible
        if (extension_loaded('pdo_mysql')) {
            echo '<div class="alert alert-success">✅ Extension PDO MySQL détectée</div>';
        } else {
            echo '<div class="alert alert-danger">❌ Extension PDO MySQL manquante - Activez-la dans php.ini</div>';
        }
        
        // Vérifier WAMP/XAMPP
        if (file_exists('C:/wamp64/') || file_exists('C:/xampp/')) {
            echo '<div class="alert alert-success">✅ WAMP/XAMPP détecté</div>';
        } else {
            echo '<div class="alert alert-warning">⚠️ WAMP/XAMPP non détecté - Assurez-vous que MySQL est installé</div>';
        }
        ?>
    </div>

    <div class="step">
        <h3>🚀 Étape 2 : Démarrer MySQL</h3>
        <div class="alert alert-info">
            <strong>Avec WAMP :</strong>
            <ol>
                <li>Cliquez sur l'icône WAMP dans la barre des tâches</li>
                <li>Vérifiez que l'icône est verte</li>
                <li>Si rouge/orange : cliquez → "Restart All Services"</li>
            </ol>
            
            <strong>Avec XAMPP :</strong>
            <ol>
                <li>Ouvrez le panneau de contrôle XAMPP</li>
                <li>Cliquez sur "Start" à côté de MySQL</li>
                <li>Vérifiez que le statut est vert</li>
            </ol>
        <!-- </div> -->
    </div>

    <div class="step">
        <h3>🗃️ Étape 3 : Créer la base de données</h3>
        
        <p><strong>Option A : Via phpMyAdmin (Recommandé)</strong></p>
        <ol>
            <li>Ouvrez <a href="http://localhost/phpmyadmin" target="_blank">http://localhost/phpmyadmin</a></li>
            <li>Cliquez sur "Nouvelle base de données"</li>
            <li>Nom : <code>memory_game</code></li>
            <li>Interclassement : <code>utf8mb4_unicode_ci</code></li>
            <li>Cliquez sur "Créer"</li>
            <li>Allez dans l'onglet "SQL" et collez le contenu ci-dessous :</li>
        </ol>
        
        <div class="code"><?php echo htmlspecialchars(file_get_contents(__DIR__ . '/config/database.sql')); ?></div>
        
        <p><strong>Option B : Ligne de commande MySQL</strong></p>
        <div class="code">mysql -u root -p < config/database.sql</div>
    </div>

    <div class="step">
        <h3>⚙️ Étape 4 : Configuration de connexion</h3>
        <p>Modifiez le fichier <code>config/DatabaseConfig.php</code> si nécessaire :</p>
        
        <div class="code">// Configuration par défaut (WAMP)
const DB_HOST = 'localhost';
const DB_NAME = 'memory_game';
const DB_USER = 'root';
const DB_PASS = '';  // Vide par défaut pour WAMP

// Pour XAMPP ou autres configurations
const DB_PASS = 'votre_mot_de_passe';</div>
    </div>

    <div class="step">
        <h3>🧪 Étape 5 : Test de connexion</h3>
        
        <?php
        // Test de connexion
        try {
            require_once 'config/DatabaseConfig.php';
            $result = DatabaseConfig::testConnection();
            
            if ($result['success']) {
                echo '<div class="alert alert-success">✅ ' . $result['message'] . '</div>';
                
                // Tester les tables
                try {
                    require_once 'classes/Database.php';
                    $db = new Database();
                    echo '<div class="alert alert-success">✅ Tables de la base de données accessibles</div>';
                    
                    // Statistiques
                    $stats = $db->getGlobalStats();
                    echo '<div class="alert alert-info">';
                    echo '<strong>📊 Statistiques actuelles :</strong><br>';
                    echo 'Joueurs : ' . ($stats['total_players'] ?? 0) . '<br>';
                    echo 'Parties : ' . ($stats['total_games'] ?? 0) . '<br>';
                    echo 'Meilleur score : ' . ($stats['highest_score'] ?? 0);
                    echo '</div>';
                    
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger">❌ Erreur d\'accès aux tables : ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                
            } else {
                echo '<div class="alert alert-danger">❌ ' . $result['message'] . '</div>';
                echo '<div class="alert alert-info">';
                echo '<strong>Solutions possibles :</strong><br>';
                echo '• Vérifiez que MySQL est démarré<br>';
                echo '• Créez la base de données "memory_game"<br>';
                echo '• Vérifiez les paramètres dans config/DatabaseConfig.php<br>';
                echo '• Exécutez le script SQL fourni';
                echo '</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">❌ Erreur : ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>

    <div class="step">
        <h3>🎮 Étape 6 : Lancer le jeu</h3>
        
        <?php if (isset($result) && $result['success']): ?>
            <div class="alert alert-success">
                <strong>🎉 Installation terminée avec succès !</strong><br>
                Votre base de données MySQL est configurée et fonctionnelle.
            </div>
            
            <div class="nav-links">
                <a href="index.php">🎮 Jouer maintenant</a>
                <a href="test.php">🧪 Tests complets</a>
                <a href="leaderboard.php">🏆 Classement</a>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                ⚠️ Veuillez d'abord résoudre les problèmes de connexion ci-dessus.
            </div>
        <?php endif; ?>
    </div>

    <div class="step">
        <h3>💡 Informations complémentaires</h3>
        
        <div class="alert alert-info">
            <strong>Avantages de MySQL vs SQLite :</strong>
            <ul>
                <li>✅ <strong>Performance :</strong> Meilleure pour de nombreux utilisateurs simultanés</li>
                <li>✅ <strong>Fonctionnalités :</strong> Support complet des transactions et contraintes</li>
                <li>✅ <strong>Scalabilité :</strong> Peut gérer des millions d'enregistrements</li>
                <li>✅ <strong>Administration :</strong> Interface phpMyAdmin pour la gestion</li>
                <li>✅ <strong>Backup :</strong> Outils de sauvegarde intégrés</li>
                <li>✅ <strong>Compatibilité :</strong> Standard industrie</li>
            </ul>
        </div>
        
        <div class="alert alert-info">
            <strong>Gestion de la base de données :</strong>
            <ul>
                <li><strong>Backup :</strong> Utilisez phpMyAdmin → Exporter</li>
                <li><strong>Vider les données :</strong> <code>$db->clearAllData()</code></li>
                <li><strong>Monitoring :</strong> Consultez les logs MySQL</li>
                <li><strong>Optimisation :</strong> Index automatiques créés</li>
            </ul>
        </div>
    </div>

    <div style="text-align: center; margin: 40px 0;">
        <a href="index.php" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold;">
            🎮 Commencer à jouer avec MySQL
        </a>
    </div>

</body>
</html> -->