<?php
/**
 * Memory Game - Page principale
 * Gestion de l'authentification et du jeu de mémoire
 */

session_start();

// Configuration des erreurs (à désactiver en production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclusion des dépendances
require_once 'config/DatabaseConfig.php';
require_once 'config/Config.php';
require_once 'classes/Database.php';
require_once 'classes/Player.php';
require_once 'classes/Game.php';
require_once 'classes/Card.php';

// Initialisation
try {
    $db = new Database();
    $player = new Player($db);
    $message = '';
    $error = '';
} catch (Exception $e) {
    die("Erreur d'initialisation: " . htmlspecialchars($e->getMessage()) . 
        "<br><a href='install.php'>Installer la base de données</a>");
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
// TRAITEMENT DES ACTIONS POST
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    switch ($_POST['action']) {
        
        // ----------------------------------------------------------------
        // AUTHENTIFICATION - Connexion
        // ----------------------------------------------------------------
        case 'login':
            $username = trim($_POST['username'] ?? '');
            
            if (empty($username)) {
                $error = "Nom d'utilisateur requis.";
                break;
            }
            
            try {
                if ($player->login($username)) {
                    $_SESSION['player_id'] = $player->getId();
                    $_SESSION['player_username'] = $player->getUsername();
                    session_write_close();
                    header('Location: index.php');
                    exit;
                }
                $error = "Utilisateur non trouvé. Essayez de vous inscrire.";
            } catch (Exception $e) {
                $error = "Erreur de connexion: " . htmlspecialchars($e->getMessage());
            }
            break;
            
        // ----------------------------------------------------------------
        // AUTHENTIFICATION - Inscription
        // ----------------------------------------------------------------
        case 'register':
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            
            if (empty($username)) {
                $error = "Nom d'utilisateur requis.";
                break;
            }
            
            try {
                if ($player->register($username, $email ?: null)) {
                    $_SESSION['player_id'] = $player->getId();
                    $_SESSION['player_username'] = $player->getUsername();
                    session_write_close();
                    header('Location: index.php');
                    exit;
                }
                $error = "Nom d'utilisateur déjà utilisé.";
            } catch (Exception $e) {
                $error = "Erreur d'inscription: " . htmlspecialchars($e->getMessage());
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
        // JEU - Nouveau jeu
        // ----------------------------------------------------------------
        case 'new_game':
            if (!isset($_SESSION['player_id'])) {
                $error = "Veuillez vous connecter pour jouer.";
                break;
            }
            
            try {
                $pairs = Config::validatePairs($_POST['pairs'] ?? 6);
                $game = new Game($pairs, $_SESSION['player_id']);
                $_SESSION['current_game'] = serialize($game);
                $_SESSION['game_start_time'] = time();
                session_write_close();
                header('Location: index.php');
                exit;
            } catch (Exception $e) {
                $error = "Erreur lors de la création du jeu: " . htmlspecialchars($e->getMessage());
            }
            break;
            
        // ----------------------------------------------------------------
        // JEU - Retourner une carte
        // ----------------------------------------------------------------
        case 'flip_card':
            if (!isset($_SESSION['current_game'], $_SESSION['player_id'])) {
                $error = "Session expirée. Veuillez vous reconnecter.";
                break;
            }
            
            try {
                $game = unserialize($_SESSION['current_game']);
                $cardId = (int)($_POST['card_id'] ?? -1);

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
                        $playerForScore->login($_SESSION['player_username']);
                        $playerForScore->saveGameScore(
                            $stats['pairs'], 
                            $stats['moves'], 
                            $gameTime, 
                            $stats['score']
                        );
                        $_SESSION['game_message'] = "🎉 Félicitations ! Jeu terminé en {$stats['moves']} coups !";
                    } 
                    // Pas de paire trouvée - déclencher auto-reset
                    elseif ($result === 'no_pair') {
                        $_SESSION['pending_reset'] = true;
                    }

                    // Mémoriser la carte pour l'ancrage
                    $_SESSION['last_card'] = $cardId;
                    session_write_close();
                    header('Location: index.php#card-' . $cardId);
                    exit;
                }
            } catch (Exception $e) {
                $error = "Erreur de jeu: " . htmlspecialchars($e->getMessage());
            }
            break;
            
        // ----------------------------------------------------------------
        // JEU - Réinitialiser les cartes retournées
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
                $error = "Erreur lors du reset: " . htmlspecialchars($e->getMessage());
            }
            break;
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
            <h1>🎮 Memory Game</h1>
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
            
            <!-- ============================================ -->
            <!-- NON CONNECTÉ - Formulaire d'authentification -->
            <!-- ============================================ -->
            
            <div class="auth-section">
                <div class="auth-card">
                    <h2>Se connecter ou créer un compte</h2>
                    <form method="POST" class="auth-form">
                        <input type="text" name="username" placeholder="Nom d'utilisateur" required>
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
                
                <!-- Statistiques du jeu -->
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
                            <button type="submit" name="action" value="new_game" class="btn btn-primary">
                                Rejouer
                            </button>
                            <input type="hidden" name="pairs" value="<?= $stats['pairs'] ?>">
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Plateau de jeu -->
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
                                <form method="POST" class="card-form">
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
                
                <!-- Bouton de reset manuel (si 2 cartes retournées) -->
                <?php if ($stats['flippedCards'] == 2 && !$stats['isCompleted']): ?>
                <form method="POST" style="text-align: center; margin: 20px 0;">
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