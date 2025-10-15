<?php
session_start();
require_once 'classes/Database.php';
require_once 'classes/Player.php';

$db = new Database();
$player = new Player($db);
$message = '';
$error = '';

// Traitement des actions POST
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'login':
                $username = trim($_POST['username'] ?? '');
                if (!empty($username)) {
                    if ($player->login($username)) {
                        $_SESSION['profile_player_id'] = $player->getId();
                        $_SESSION['profile_player_username'] = $player->getUsername();
                        $message = "Connexion réussie !";
                    } else {
                        $error = "Utilisateur non trouvé.";
                    }
                } else {
                    $error = "Nom d'utilisateur requis.";
                }
                break;
                
            case 'logout':
                unset($_SESSION['profile_player_id']);
                unset($_SESSION['profile_player_username']);
                header('Location: profile.php');
                exit;
                break;
        }
    }
}

// Récupérer le joueur connecté
$currentPlayer = null;
$playerStats = null;
$playerHistory = [];
if (isset($_SESSION['profile_player_id'])) {
    $player->login($_SESSION['profile_player_username']);
    $currentPlayer = $player;
    $playerStats = $player->getStats();
    $playerHistory = $player->getRecentScores(20);
}

function formatTime($seconds) {
    if (!$seconds) return '--:--';
    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;
    return sprintf('%d:%02d', $minutes, $remainingSeconds);
}

function formatDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('d/m/Y H:i');
}

function checkAchievement($stats, $condition) {
    switch ($condition) {
        case 'total_games >= 1':
            return ($stats['total_games'] ?? 0) >= 1;
        case 'total_games >= 10':
            return ($stats['total_games'] ?? 0) >= 10;
        case 'total_games >= 100':
            return ($stats['total_games'] ?? 0) >= 100;
        case 'best_time < 60':
            return ($stats['best_time'] ?? 999) < 60;
        case 'best_moves <= 6':
            return ($stats['best_moves'] ?? 999) <= 6;
        case 'best_score >= 5000':
            return ($stats['best_score'] ?? 0) >= 5000;
        case 'ranking <= 10':
            return ($stats['ranking'] ?? 999) <= 10;
        default:
            return false;
    }
}

$achievements = [
    [
        'icon' => '🎮',
        'title' => 'Premier Pas',
        'desc' => 'Jouer sa première partie',
        'condition' => 'total_games >= 1'
    ],
    [
        'icon' => '🔥',
        'title' => 'Joueur Régulier',
        'desc' => 'Jouer 10 parties',
        'condition' => 'total_games >= 10'
    ],
    [
        'icon' => '💯',
        'title' => 'Centenaire',
        'desc' => 'Jouer 100 parties',
        'condition' => 'total_games >= 100'
    ],
    [
        'icon' => '⚡',
        'title' => 'Démon de Vitesse',
        'desc' => 'Terminer une partie en moins de 60 secondes',
        'condition' => 'best_time < 60'
    ],
    [
        'icon' => '🎯',
        'title' => 'Perfectionniste',
        'desc' => 'Terminer une partie avec le minimum de coups',
        'condition' => 'best_moves <= 6'
    ],
    [
        'icon' => '🏆',
        'title' => 'Gros Score',
        'desc' => 'Obtenir un score de 5000 points',
        'condition' => 'best_score >= 5000'
    ],
    [
        'icon' => '🥇',
        'title' => 'Top 10',
        'desc' => 'Être dans le top 10',
        'condition' => 'ranking <= 10'
    ],
    [
        'icon' => '🧠',
        'title' => 'Niveau Expert',
        'desc' => 'Terminer une partie de 12 paires',
        'condition' => 'expert_level'
    ]
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Memory Game</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>🧠 Memory Game</h1>
            <nav class="nav">
                <a href="index.php" class="nav-link">Jouer</a>
                <a href="leaderboard.php" class="nav-link">Classement</a>
                <a href="profile.php" class="nav-link active">Profil</a>
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
            <div class="auth-section">
                <div class="auth-card">
                    <h2>Connectez-vous pour voir votre profil</h2>
                    <form method="POST" class="auth-form">
                        <input type="text" name="username" placeholder="Nom d'utilisateur" required>
                        <button type="submit" name="action" value="login" class="btn btn-primary">Se connecter</button>
                    </form>
                    <p><a href="index.php">Retour au jeu</a></p>
                </div>
            </div>
            <?php else: ?>
            <div class="profile-section">
                <div class="profile-header">
                    <h2>👤 <?php echo htmlspecialchars($currentPlayer->getUsername()); ?></h2>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="action" value="logout" class="btn btn-small">Déconnexion</button>
                    </form>
                </div>

                <div class="profile-stats">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h3>🎮 Parties jouées</h3>
                            <div class="stat-value"><?php echo $playerStats['total_games'] ?? 0; ?></div>
                        </div>
                        <div class="stat-card">
                            <h3>🏆 Meilleur score</h3>
                            <div class="stat-value"><?php echo $playerStats['best_score'] ?? 0; ?></div>
                        </div>
                        <div class="stat-card">
                            <h3>📊 Score moyen</h3>
                            <div class="stat-value"><?php echo round($playerStats['avg_score'] ?? 0); ?></div>
                        </div>
                        <div class="stat-card">
                            <h3>⚡ Meilleur temps</h3>
                            <div class="stat-value"><?php echo formatTime($playerStats['best_time'] ?? null); ?></div>
                        </div>
                        <div class="stat-card">
                            <h3>🎯 Moins de coups</h3>
                            <div class="stat-value"><?php echo $playerStats['best_moves'] ?? '--'; ?></div>
                        </div>
                        <div class="stat-card">
                            <h3>📈 Classement</h3>
                            <div class="stat-value">#<?php echo $playerStats['ranking'] ?? '--'; ?></div>
                        </div>
                    </div>
                </div>

                <div class="profile-history">
                    <h3>📜 Historique des parties</h3>
                    <div class="history-controls">
                        <a href="profile.php" class="btn btn-secondary">🔄 Actualiser</a>
                    </div>
                    <div class="history-table">
                        <div class="history-header">
                            <div class="pairs">Paires</div>
                            <div class="score">Score</div>
                            <div class="moves">Coups</div>
                            <div class="time">Temps</div>
                            <div class="date">Date</div>
                        </div>
                        <div class="history-content">
                            <?php if (empty($playerHistory)): ?>
                                <div style="text-align: center; padding: 40px; color: #666;">
                                    Aucune partie jouée pour le moment
                                </div>
                            <?php else: ?>
                                <?php foreach ($playerHistory as $score): ?>
                                    <div class="history-row">
                                        <div class="pairs"><?php echo $score['pairs']; ?></div>
                                        <div class="score"><?php echo $score['score']; ?></div>
                                        <div class="moves"><?php echo $score['moves']; ?></div>
                                        <div class="time"><?php echo formatTime($score['time']); ?></div>
                                        <div class="date"><?php echo formatDate($score['created_at']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="profile-achievements">
                    <h3>🏅 Accomplissements</h3>
                    <div class="achievements-grid">
                        <?php foreach ($achievements as $achievement): ?>
                            <?php $unlocked = checkAchievement($playerStats, $achievement['condition']); ?>
                            <div class="achievement <?php echo $unlocked ? 'unlocked' : 'locked'; ?>">
                                <div class="achievement-icon"><?php echo $achievement['icon']; ?></div>
                                <div class="achievement-title"><?php echo $achievement['title']; ?></div>
                                <div class="achievement-desc"><?php echo $achievement['desc']; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <a href="index.php" class="btn btn-primary">🎮 Jouer maintenant</a>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>