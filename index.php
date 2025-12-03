<?php

// ============================================
// CONFIGURATION SÉCURITÉ
// ============================================

// Headers de sécurité
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Configuration session sécurisée
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

session_start();

// Configuration des erreurs selon environnement
$isLocalhost = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']);

if ($isLocalhost) {
    // Développement
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // Production
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/error.log');
    
    // Gestionnaire d'erreurs personnalisé
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        error_log("Erreur [$errno]: $errstr dans $errfile:$errline");
        return true;
    });
}

if (file_exists(__DIR__ . '/../DatabaseConfig.php')) {
    require_once __DIR__ . '/../DatabaseConfig.php';
} else {
    require_once __DIR__ . '/config/DatabaseConfig.php';
}
require_once 'config/Config.php';
require_once 'classes/Database.php';
require_once 'classes/Player.php';
require_once 'classes/Game.php';
require_once 'classes/Card.php';
require_once 'classes/Security.php';

// Initialisation
try {
    $db = new Database();
    $player = new Player($db);
    $message = '';
    $error = '';
} catch (Exception $e) {
    if ($isLocalhost) {
        die("Erreur d'initialisation: " . htmlspecialchars($e->getMessage()) . 
            "<br><a href='install.php'>Installer la base de données</a>");
    } else {
        error_log("Erreur d'initialisation: " . $e->getMessage());
        die("Service temporairement indisponible. Veuillez réessayer plus tard.");
    }
}

// Nettoyage des sessions corrompues
if (isset($_SESSION['current_game'])) {
    try {
        $game = unserialize($_SESSION['current_game']);
        if (!$game || !is_object($game) || !method_exists($game, 'getStats')) {
            unset($_SESSION['current_game'], $_SESSION['game_start_time']);
        }
    } catch (Exception $e) {
        unset($_SESSION['current_game'], $_SESSION['game_start_time']);
    }
}

// ============================================================================
// TRAITEMENT DES ACTIONS POST SÉCURISÉES
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // ✅ VALIDATION CSRF (sauf pour login/register)
    $publicActions = ['login', 'register'];
    if (!in_array($_POST['action'], $publicActions)) {
        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = "Token de sécurité invalide. Veuillez recharger la page.";
        }
    }
    
    if (empty($error)) {
        switch ($_POST['action']) {
            
            // ----------------------------------------------------------------
            // AUTHENTIFICATION - Connexion SÉCURISÉE
            // ----------------------------------------------------------------
            case 'login':
                if (!Security::checkRateLimit('login', 5, 900)) {
                    $error = "Trop de tentatives de connexion. Attendez 15 minutes.";
                    break;
                }
                
                $username = Security::sanitize($_POST['username'] ?? '', 'username');
                $password = $_POST['password'] ?? '';
                
                if (empty($username) || empty($password)) {
                    $error = "Nom d'utilisateur et mot de passe requis.";
                    Security::recordAttempt('login');
                    break;
                }
                
                if (!Security::validateUsername($username)) {
                    $error = "Format de nom d'utilisateur invalide.";
                    Security::recordAttempt('login');
                    break;
                }
                
                try {
                    if ($player->login($username, $password)) {
                        // Régénération session (sécurité)
                        session_regenerate_id(true);
                        $_SESSION['player_id'] = $player->getId();
                        $_SESSION['player_username'] = $player->getUsername();
                        $_SESSION['login_time'] = time();
                        Security::generateCSRFToken(); // Nouveau token
                        
                        session_write_close();
                        header('Location: index.php');
                        exit;
                    }
                    $error = "Nom d'utilisateur ou mot de passe incorrect.";
                    Security::recordAttempt('login');
                } catch (Exception $e) {
                    $error = "Erreur de connexion.";
                    Security::recordAttempt('login');
                    if ($isLocalhost) {
                        $error .= " " . htmlspecialchars($e->getMessage());
                    }
                }
                break;
                
            // ----------------------------------------------------------------
            // AUTHENTIFICATION - Inscription SÉCURISÉE
            // ----------------------------------------------------------------
            case 'register':
                if (!Security::checkRateLimit('register', 3, 3600)) {
                    $error = "Trop d'inscriptions tentées. Attendez 1 heure.";
                    break;
                }
                
                $username = Security::sanitize($_POST['username'] ?? '', 'username');
                $password = $_POST['password'] ?? '';
                $email = Security::sanitize($_POST['email'] ?? '', 'email');
                
                if (empty($username) || empty($password)) {
                    $error = "Nom d'utilisateur et mot de passe requis.";
                    Security::recordAttempt('register');
                    break;
                }
                
                if (!Security::validateUsername($username)) {
                    $error = "Le nom d'utilisateur doit contenir 3-20 caractères alphanumériques uniquement.";
                    break;
                }
                
                if (!Security::validatePassword($password)) {
                    $error = "Le mot de passe doit contenir au moins 8 caractères, 1 majuscule et 1 chiffre.";
                    break;
                }
                
                if (!empty($email) && !Security::validateEmail($email)) {
                    $error = "Format d'email invalide.";
                    break;
                }
                
                try {
                    if ($player->register($username, $password, $email ?: null)) {
                        session_regenerate_id(true);
                        $_SESSION['player_id'] = $player->getId();
                        $_SESSION['player_username'] = $player->getUsername();
                        $_SESSION['login_time'] = time();
                        Security::generateCSRFToken();
                        
                        session_write_close();
                        header('Location: index.php');
                        exit;
                    }
                    $error = "Nom d'utilisateur déjà utilisé.";
                    Security::recordAttempt('register');
                } catch (Exception $e) {
                    $error = "Erreur d'inscription.";
                    Security::recordAttempt('register');
                    if ($isLocalhost) {
                        $error .= " " . htmlspecialchars($e->getMessage());
                    }
                }
                break;
                
            // ----------------------------------------------------------------
            // AUTHENTIFICATION - Déconnexion
            // ----------------------------------------------------------------
            case 'logout':
                session_destroy();
                header('Location: index.php');
                exit;
                
            // ----------------------------------------------------------------
            // JEU - Nouveau jeu (avec CSRF)
            // ----------------------------------------------------------------
            case 'new_game':
                if (!isset($_SESSION['player_id'])) {
                    $error = "Veuillez vous connecter pour jouer.";
                    break;
                }
                
                try {
                    $pairs = Security::sanitize($_POST['pairs'] ?? 6, 'int');
                    $pairs = Config::validatePairs($pairs);
                    
                    $game = new Game($pairs, $_SESSION['player_id']);
                    $_SESSION['current_game'] = serialize($game);
                    $_SESSION['game_start_time'] = time();
                    session_write_close();
                    header('Location: index.php');
                    exit;
                } catch (Exception $e) {
                    $error = "Erreur lors de la création du jeu.";
                    if ($isLocalhost) {
                        $error .= " " . htmlspecialchars($e->getMessage());
                    }
                }
                break;
                
            // ----------------------------------------------------------------
            // JEU - Retourner une carte (avec CSRF)
            // ----------------------------------------------------------------
            case 'flip_card':
                if (!isset($_SESSION['current_game'], $_SESSION['player_id'])) {
                    $error = "Session expirée. Veuillez vous reconnecter.";
                    break;
                }
                
                try {
                    $game = unserialize($_SESSION['current_game']);
                    $cardId = Security::sanitize($_POST['card_id'] ?? -1, 'int');

                    if (!$game || !method_exists($game, 'flipCard')) {
                        throw new Exception("Jeu invalide");
                    }

                    $result = $game->flipCard($cardId);
                    
                    if ($result) {
                        $_SESSION['current_game'] = serialize($game);
                        $stats = $game->getStats();

                        // Jeu terminé
                        if ($stats['isCompleted']) {
                            $gameTime = time() - $_SESSION['game_start_time'];
                            $playerForScore = new Player($db);
                            $playerForScore->login($_SESSION['player_username'], ''); // Login sans password pour score
                            $playerForScore->saveGameScore(
                                $stats['pairs'], 
                                $stats['moves'], 
                                $gameTime, 
                                $stats['score']
                            );
                            $_SESSION['game_message'] = "🎉 Félicitations ! Jeu terminé en {$stats['moves']} coups !";
                        } 
                        // Pas de paire trouvée
                        elseif ($result === 'no_pair') {
                            $_SESSION['pending_reset'] = true;
                        }

                        $_SESSION['last_card'] = $cardId;
                        session_write_close();
                        header('Location: index.php#card-' . $cardId);
                        exit;
                    }
                } catch (Exception $e) {
                    $error = "Erreur de jeu.";
                    if ($isLocalhost) {
                        $error .= " " . htmlspecialchars($e->getMessage());
                    }
                }
                break;
                
            // ----------------------------------------------------------------
            // JEU - Reset cartes (avec CSRF)
            // ----------------------------------------------------------------
            case 'reset_flipped':
                if (!isset($_SESSION['current_game'])) {
                    break;
                }
                
                try {
                    $game = unserialize($_SESSION['current_game']);
                    if ($game && method_exists($game, 'resetFlippedCards')) {
                        $game->resetFlippedCards();
                        $_SESSION['current_game'] = serialize($game);
                        session_write_close();
                        header('Location: index.php');
                        exit;
                    }
                } catch (Exception $e) {
                    $error = "Erreur lors du reset.";
                    if ($isLocalhost) {
                        $error .= " " . htmlspecialchars($e->getMessage());
                    }
                }
                break;
        }
    }
}

// ============================================================================
// TRAITEMENT GET - Auto-reset sans JavaScript
// ============================================================================

if (isset($_GET['do']) && $_GET['do'] === 'reset' && isset($_SESSION['current_game'])) {
    try {
        $game = unserialize($_SESSION['current_game']);
        if ($game && method_exists($game, 'resetFlippedCards')) {
            $game->resetFlippedCards();
            $_SESSION['current_game'] = serialize($game);
            unset($_SESSION['pending_reset']);
        }
    } catch (Exception $e) {
        // Erreur silencieuse pour le reset
    }
    
    $anchor = isset($_SESSION['last_card']) ? '#card-' . (int)$_SESSION['last_card'] : '';
    session_write_close();
    header('Location: index.php' . $anchor);
    exit;
}

// ============================================================================
// RÉCUPÉRATION DES DONNÉES
// ============================================================================

// Message de succès
if (isset($_SESSION['game_message'])) {
    $message = $_SESSION['game_message'];
    unset($_SESSION['game_message']);
}

// Joueur connecté (simple objet avec données de session)
$currentPlayer = null;
if (isset($_SESSION['player_id'], $_SESSION['player_username'])) {
    $currentPlayer = (object)[
        'id' => $_SESSION['player_id'],
        'username' => $_SESSION['player_username']
    ];
}

// Jeu en cours
$currentGame = null;
if (isset($_SESSION['current_game'])) {
    try {
        $currentGame = unserialize($_SESSION['current_game']);
        if (!$currentGame || !is_object($currentGame)) {
            unset($_SESSION['current_game']);
            $currentGame = null;
        }
    } catch (Exception $e) {
        unset($_SESSION['current_game']);
        $currentGame = null;
    }
}

// ============================================================================
// AFFICHAGE HTML
// ============================================================================
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memory Game - Jeu de Mémoire</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    
    <?php if (!empty($_SESSION['pending_reset'])): 
        $lastAnchor = isset($_SESSION['last_card']) ? '#card-' . (int)$_SESSION['last_card'] : '';
    ?>
        <meta http-equiv="refresh" content="1;url=index.php?do=reset<?= $lastAnchor ?>">
    <?php endif; ?>
</head>

<body class="memory-page">
    <div class="container">
        
        <!-- En-tête -->
        <header class="header">
            <h1> Memory Game</h1>
            <nav class="nav">
                <a href="index.php" class="nav-link active">Jouer</a>
                <a href="leaderboard.php" class="nav-link">Classement</a>
                <a href="profile.php" class="nav-link">Profil</a>
            </nav>
        </header>

        <main class="main">
            
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="message success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!$currentPlayer): ?>
            
            <!-- Formulaire d'authentification SÉCURISÉ -->
            <div class="auth-section">
                <div class="auth-card">
                    <h2>Se connecter ou créer un compte</h2>
                    <form method="POST" class="auth-form">
                        <input type="text" name="username" placeholder="Nom d'utilisateur (3-20 caractères)" required maxlength="20">
                        <input type="password" name="password" placeholder="Mot de passe (min 8 caractères, 1 majuscule, 1 chiffre)" required minlength="8">
                        <input type="email" name="email" placeholder="Email (optionnel)">
                        <div class="auth-buttons">
                            <button type="submit" name="action" value="login" class="btn btn-primary">
                                Se connecter
                            </button>
                            <button type="submit" name="action" value="register" class="btn btn-secondary">
                                S'inscrire
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php else: ?>
            
            <!-- ============================================ -->
            <!-- CONNECTÉ - Interface de jeu                 -->
            <!-- ============================================ -->
            
            <div class="game-section">
                
                <!-- Contrôles du jeu -->
                <div class="game-controls">
                    <div class="difficulty-selector">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCSRFToken()) ?>">
                            <label for="pairs">Nombre de paires :</label>
                            <select name="pairs" id="pairs">
                                <?php for ($i = 3; $i <= 12; $i++): 
                                    $selected = ($currentGame && $currentGame->getStats()['pairs'] == $i) ? 'selected' : '';
                                    $difficulty = $i == 3 ? '(Facile)' : ($i == 6 ? '(Normal)' : ($i >= 8 ? '(Difficile)' : ''));
                                ?>
                                    <option value="<?= $i ?>" <?= $selected ?>>
                                        <?= $i ?> paires <?= $difficulty ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <button type="submit" name="action" value="new_game" class="btn btn-primary">
                                Nouveau Jeu
                            </button>
                        </form>
                    </div>
                    
                    <div class="player-info">
                        <span>👤 <?= htmlspecialchars($currentPlayer->username) ?></span>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCSRFToken()) ?>">
                            <button type="submit" name="action" value="logout" class="btn btn-small">
                                Déconnexion
                            </button>
                        </form>
                    </div>
                </div>

                <?php if ($currentGame): 
                    $stats = $currentGame->getStats();
                    $gameTime = isset($_SESSION['game_start_time']) ? (time() - $_SESSION['game_start_time']) : 0;
                    $timeFormatted = sprintf('%02d:%02d', floor($gameTime / 60), $gameTime % 60);
                ?>
                
                
                <div class="game-board grid-<?= ceil(sqrt(count($currentGame->getCards()))) ?>">
                    <?php foreach ($currentGame->getCards() as $card): ?>
                        <div id="card-<?= $card['id'] ?>" class="card-container <?= $card['isMatched'] ? 'matched' : '' ?>">
                            
                            <?php if ($card['isMatched']): ?>
                                <!-- Carte trouvée -->
                                <div class="card face front matched" style="background-color: <?= htmlspecialchars($card['color']) ?>;">
                                    <img src="<?= htmlspecialchars($card['image']) ?>" alt="Carte trouvée" class="card-img">
                                </div>
                                
                            <?php elseif ($card['isFlipped']): ?>
                                <!-- Carte retournée temporairement -->
                                <div class="card face front flipped" style="background-color: <?= htmlspecialchars($card['color']) ?>;">
                                    <img src="<?= htmlspecialchars($card['image']) ?>" alt="Carte retournée" class="card-img">
                                </div>
                                
                            <?php else: ?>
                                <!-- Carte cachée -->
                                <!-- ✅ Formulaire avec CSRF -->
                                <form method="POST" class="card-form">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCSRFToken()) ?>">
                                    <button type="submit" name="action" value="flip_card" 
                                            class="card face back" 
                                            aria-label="Retourner la carte <?= $card['id'] ?>">
                                        ?
                                    </button>
                                    <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
                                </form>
                            <?php endif; ?>
                            
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- ✅ 2. STATISTIQUES EN DESSOUS (mais affichées en haut avec CSS) -->
                <div class="game-stats">
                    <div class="stat">
                        <span class="stat-label">Coups :</span>
                        <span><?= $stats['moves'] ?></span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Temps :</span>
                        <span><?= $timeFormatted ?></span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Score :</span>
                        <span><?= $stats['score'] ?></span>
                    </div>
                </div>

                <!-- Écran de victoire -->
                <?php if ($stats['isCompleted']): ?>
                <div class="game-completed-static">
                    <div class="completion-card">
                        <h2>🎉 Félicitations !</h2>
                        <p>Vous avez terminé le jeu !</p>
                        <div class="final-stats">
                            <div class="final-stat">
                                <strong>Coups :</strong> <?= $stats['moves'] ?>
                            </div>
                            <div class="final-stat">
                                <strong>Temps :</strong> <?= $timeFormatted ?>
                            </div>
                            <div class="final-stat">
                                <strong>Score :</strong> <?= $stats['score'] ?>
                            </div>
                        </div>
                        <form method="POST" style="margin-top: 20px;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCSRFToken()) ?>">
                            <button type="submit" name="action" value="new_game" class="btn btn-primary">
                                Rejouer
                            </button>
                            <input type="hidden" name="pairs" value="<?= $stats['pairs'] ?>">
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Bouton de reset manuel (si 2 cartes retournées) -->
                <?php if ($stats['flippedCards'] == 2 && !$stats['isCompleted']): ?>
                <form method="POST" style="text-align: center; margin: 20px 0;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCSRFToken()) ?>">
                    <button type="submit" name="action" value="reset_flipped" class="btn btn-secondary">
                        Continuer (retourner les cartes)
                    </button>
                </form>
                <?php endif; ?>

                <?php else: ?>
                
                <!-- Aucun jeu en cours -->
                <div class="no-game">
                    <h3>Aucun jeu en cours</h3>
                    <p>Choisissez le nombre de paires et cliquez sur "Nouveau Jeu" pour commencer !</p>
                    <form method="POST" style="text-align: center; margin-top: 20px;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCSRFToken()) ?>">
                        <select name="pairs" style="padding: 10px; margin: 10px; border-radius: 8px;">
                            <?php for ($i = 3; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>" <?= $i == 6 ? 'selected' : '' ?>>
                                    <?= $i ?> paires
                                </option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" name="action" value="new_game" class="btn btn-primary">
                            🎮 Démarrer une partie
                        </button>
                    </form>
                </div>
                
                <?php endif; ?>
            </div>
            
            <?php endif; ?>
        </main>
    </div>
</body>
</html>