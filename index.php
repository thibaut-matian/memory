<?php
session_start();

// Gestion des erreurs et nettoyage des sessions corrompues
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Inclusion des fichiers nécessaires
    require_once 'config/DatabaseConfig.php';
    require_once 'classes/Database.php';
    require_once 'classes/Player.php';
    require_once 'classes/Game.php';
    require_once 'classes/Card.php';

    // Vérification de la session corrompue
    if (isset($_SESSION['current_game'])) {
        $game = unserialize($_SESSION['current_game']);
        if (!$game || !is_object($game) || !method_exists($game, 'isGameOver')) {
            unset($_SESSION['current_game']);
            unset($_SESSION['game_start_time']);
        }
    }

    // Initialisation
    $db = new Database();
    $player = new Player($db);
    $message = '';
    $error = '';

} catch (Exception $e) {
    die("Erreur d'initialisation: " . $e->getMessage() . "<br><a href='install.php'>Installer la base de données</a>");
}

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'login':
            $username = trim($_POST['username'] ?? '');
            if (!empty($username)) {
                try {
                    if ($player->login($username)) {
                        $_SESSION['player_id'] = $player->getId();
                        $_SESSION['player_username'] = $player->getUsername();
                        $message = "Connexion réussie !";
                    } else {
                        $error = "Utilisateur non trouvé. Essayez de vous inscrire.";
                    }
                } catch (Exception $e) {
                    $error = "Erreur de connexion: " . $e->getMessage();
                }
            } else {
                $error = "Nom d'utilisateur requis.";
            }
            break;
            
        case 'register':
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            if (!empty($username)) {
                try {
                    if ($player->register($username, $email ?: null)) {
                        $_SESSION['player_id'] = $player->getId();
                        $_SESSION['player_username'] = $player->getUsername();
                        $message = "Inscription réussie !";
                    } else {
                        $error = "Nom d'utilisateur déjà utilisé.";
                    }
                } catch (Exception $e) {
                    $error = "Erreur d'inscription: " . $e->getMessage();
                }
            } else {
                $error = "Nom d'utilisateur requis.";
            }
            break;
            
        case 'logout':
            session_destroy();
            header('Location: index.php');
            exit;
            break;
            
        case 'new_game':
            if (isset($_SESSION['player_id'])) {
                try {
                    $pairs = max(3, min(12, (int)($_POST['pairs'] ?? 6)));
                    $game = new Game($pairs, $_SESSION['player_id']);
                    $_SESSION['current_game'] = serialize($game);
                    $_SESSION['game_start_time'] = time();
                    $message = "Nouveau jeu créé avec $pairs paires !";
                } catch (Exception $e) {
                    $error = "Erreur lors de la création du jeu: " . $e->getMessage();
                }
            } else {
                $error = "Veuillez vous connecter pour jouer.";
            }
            break;
            
        case 'flip_card':
            if (isset($_SESSION['current_game']) && isset($_SESSION['player_id'])) {
                try {
                    $game = unserialize($_SESSION['current_game']);
                    $cardId = (int)($_POST['card_id'] ?? -1);
                    
                    if ($game && method_exists($game, 'flipCard')) {
                        $result = $game->flipCard($cardId);
                        if ($result) {
                            $_SESSION['current_game'] = serialize($game);
                            
                            // Vérifier si le jeu est terminé
                            $stats = $game->getStats();
                            if ($stats['isCompleted']) {
                                $player->login($_SESSION['player_username']);
                                $gameTime = time() - $_SESSION['game_start_time'];
                                $player->saveGameScore($stats['pairs'], $stats['moves'], $gameTime, $stats['score']);
                                $message = "🎉 Félicitations ! Jeu terminé en {$stats['moves']} coups !";
                            } elseif ($result === 'no_pair') {
                                // Déclencher un auto-reset après 1 seconde sans JS via redirection
                                $_SESSION['pending_reset'] = true;
                            }
                        }
                    }
                } catch (Exception $e) {
                    $error = "Erreur de jeu: " . $e->getMessage();
                }
            }
            break;
            
        case 'reset_flipped':
            if (isset($_SESSION['current_game'])) {
                try {
                    $game = unserialize($_SESSION['current_game']);
                    if ($game && method_exists($game, 'resetFlippedCards')) {
                        $game->resetFlippedCards();
                        $_SESSION['current_game'] = serialize($game);
                    }
                } catch (Exception $e) {
                    $error = "Erreur lors du reset: " . $e->getMessage();
                }
            }
            break;
    }
}

// Traitement GET pour exécuter le reset demandé (sans JS)
if (isset($_GET['do']) && $_GET['do'] === 'reset' && isset($_SESSION['current_game'])) {
    $game = unserialize($_SESSION['current_game']);
    if ($game && method_exists($game, 'resetFlippedCards')) {
        $game->resetFlippedCards();
        $_SESSION['current_game'] = serialize($game);
        unset($_SESSION['pending_reset']);
    }
    header('Location: index.php');
    exit;
}

// Récupérer le joueur connecté
$currentPlayer = null;
if (isset($_SESSION['player_id'])) {
    try {
        $player->login($_SESSION['player_username']);
        $currentPlayer = $player;
    } catch (Exception $e) {
        // Si erreur, déconnecter
        unset($_SESSION['player_id']);
        unset($_SESSION['player_username']);
    }
}

// Récupérer le jeu en cours
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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memory Game - Jeu de Mémoire</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php if (!empty($_SESSION['pending_reset'])): ?>
        <meta http-equiv="refresh" content="1;url=index.php?do=reset">
    <?php endif; ?>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>🧠 Memory Game</h1>
            <nav class="nav">
                <a href="index.php" class="nav-link active">Jouer</a>
                <a href="leaderboard.php" class="nav-link">Classement</a>
                <a href="profile.php" class="nav-link">Profil</a>
            </nav>
        </header>

        <main class="main">
            <?php if ($message): ?>
                <div class="message success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!$currentPlayer): ?>
            <!-- Section de connexion/inscription -->
            <div class="auth-section">
                <div class="auth-card">
                    <h2>Se connecter ou créer un compte</h2>
                    <form method="POST" class="auth-form">
                        <input type="text" name="username" placeholder="Nom d'utilisateur" required>
                        <input type="email" name="email" placeholder="Email (optionnel)">
                        <div class="auth-buttons">
                            <button type="submit" name="action" value="login" class="btn btn-primary">Se connecter</button>
                            <button type="submit" name="action" value="register" class="btn btn-secondary">S'inscrire</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <!-- Section de jeu -->
            <div class="game-section">
                <div class="game-controls">
                    <div class="difficulty-selector">
                        <form method="POST" style="display: inline;">
                            <label for="pairs">Nombre de paires :</label>
                            <select name="pairs" id="pairs">
                                <?php for ($i = 3; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($currentGame && $currentGame->getStats()['pairs'] == $i) ? 'selected' : ''; ?>>
                                        <?php echo $i; ?> paires <?php echo $i == 3 ? '(Facile)' : ($i == 6 ? '(Normal)' : ($i >= 8 ? '(Difficile)' : '')); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <button type="submit" name="action" value="new_game" class="btn btn-primary">Nouveau Jeu</button>
                        </form>
                    </div>
                    
                    <div class="player-info">
                        <span>👤 <?php echo htmlspecialchars($currentPlayer->getUsername()); ?></span>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="action" value="logout" class="btn btn-small">Déconnexion</button>
                        </form>
                    </div>
                </div>

                <?php if ($currentGame): ?>
                <?php 
                $stats = $currentGame->getStats();
                $gameTime = isset($_SESSION['game_start_time']) ? (time() - $_SESSION['game_start_time']) : 0;
                $minutes = floor($gameTime / 60);
                $seconds = $gameTime % 60;
                $timeFormatted = sprintf('%02d:%02d', $minutes, $seconds);
                ?>
                
                <div class="game-stats">
                    <div class="stat">
                        <span class="stat-label">Coups :</span>
                        <span><?php echo $stats['moves']; ?></span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Temps :</span>
                        <span><?php echo $timeFormatted; ?></span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Score :</span>
                        <span><?php echo $stats['score']; ?></span>
                    </div>
                </div>

                <?php if ($stats['isCompleted']): ?>
                <div class="game-completed-static">
                    <div class="completion-card">
                        <h2>🎉 Félicitations !</h2>
                        <p>Vous avez terminé le jeu !</p>
                        <div class="final-stats">
                            <div class="final-stat">
                                <strong>Coups :</strong> <?php echo $stats['moves']; ?>
                            </div>
                            <div class="final-stat">
                                <strong>Temps :</strong> <?php echo $timeFormatted; ?>
                            </div>
                            <div class="final-stat">
                                <strong>Score :</strong> <?php echo $stats['score']; ?>
                            </div>
                        </div>
                        <form method="POST" style="margin-top: 20px;">
                            <button type="submit" name="action" value="new_game" class="btn btn-primary">Rejouer</button>
                            <input type="hidden" name="pairs" value="<?php echo $stats['pairs']; ?>">
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <div class="game-board grid-<?php echo ceil(sqrt(count($currentGame->getCards()))); ?>">
                    <?php foreach ($currentGame->getCards() as $card): ?>
                        <!-- Remplacé : wrapper unique pour chaque carte -->
                        <div class="card-container <?php echo $card['isMatched'] ? 'matched' : ''; ?>">
                            <?php if ($card['isMatched']): ?>
                                <div class="card face front matched" style="background-color: <?= htmlspecialchars($card['color']) ?>;">
                                    <img src="<?= htmlspecialchars($card['image']) ?>" alt="" class="card-img">
                                </div>
                            <?php elseif ($card['isFlipped']): ?>
                                <div class="card face front flipped" style="background-color: <?= htmlspecialchars($card['color']) ?>;">
                                    <img src="<?= htmlspecialchars($card['image']) ?>" alt="" class="card-img">
                                </div>
                            <?php else: ?>
                                <form method="POST" class="card-form">
                                    <button type="submit" name="action" value="flip_card" class="card face back" aria-label="Retourner la carte">?</button>
                                    <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($stats['flippedCards'] == 2 && !$stats['isCompleted']): ?>
                <form method="POST" style="text-align: center; margin: 20px 0;">
                    <button type="submit" name="action" value="reset_flipped" class="btn btn-secondary">
                        Continuer (retourner les cartes)
                    </button>
                </form>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="no-game">
                    <h3>Aucun jeu en cours</h3>
                    <p>Choisissez le nombre de paires et cliquez sur "Nouveau Jeu" pour commencer !</p>
                    <form method="POST" style="text-align: center;">
                        <select name="pairs" style="padding: 10px; margin: 10px;">
                            <?php for ($i = 3; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $i == 6 ? 'selected' : ''; ?>>
                                    <?php echo $i; ?> paires
                                </option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" name="action" value="new_game" class="btn btn-primary">🎮 Démarrer une partie</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>