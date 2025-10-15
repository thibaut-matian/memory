<?php
require_once 'classes/Database.php';

$db = new Database();
$topScores = $db->getTopScores(10);

function formatTime($seconds) {
    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;
    return sprintf('%d:%02d', $minutes, $remainingSeconds);
}

function formatDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('d/m/Y');
}

function getMedal($rank) {
    switch ($rank) {
        case 1: return '🥇 ';
        case 2: return '🥈 ';
        case 3: return '🥉 ';
        default: return '';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classement - Memory Game</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>🧠 Memory Game</h1>
            <nav class="nav">
                <a href="index.php" class="nav-link">Jouer</a>
                <a href="leaderboard.php" class="nav-link active">Classement</a>
                <a href="profile.php" class="nav-link">Profil</a>
            </nav>
        </header>

        <main class="main">
            <div class="leaderboard-section">
                <h2>🏆 Classement des Meilleurs Joueurs</h2>
                
                <div class="leaderboard-controls">
                    <a href="leaderboard.php" class="btn btn-primary">🔄 Actualiser</a>
                </div>

                <div class="leaderboard">
                    <div class="leaderboard-header">
                        <div class="rank">Rang</div>
                        <div class="player">Joueur</div>
                        <div class="score">Score</div>
                        <div class="pairs">Paires</div>
                        <div class="moves">Coups</div>
                        <div class="time">Temps</div>
                        <div class="date">Date</div>
                    </div>
                    <div class="leaderboard-content">
                        <?php if (empty($topScores)): ?>
                            <div style="text-align: center; padding: 40px; color: #666;">
                                Aucun score enregistré pour le moment
                            </div>
                        <?php else: ?>
                            <?php foreach ($topScores as $index => $score): ?>
                                <div class="leaderboard-row">
                                    <div class="rank"><?php echo getMedal($index + 1) . ($index + 1); ?></div>
                                    <div class="player"><?php echo htmlspecialchars($score['username']); ?></div>
                                    <div class="score"><?php echo $score['score']; ?></div>
                                    <div class="pairs"><?php echo $score['pairs']; ?></div>
                                    <div class="moves"><?php echo $score['moves']; ?></div>
                                    <div class="time"><?php echo formatTime($score['time']); ?></div>
                                    <div class="date"><?php echo formatDate($score['created_at']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="leaderboard-info">
                    <p>💡 <strong>Système de score :</strong></p>
                    <ul>
                        <li>Score de base : Nombre de paires × 1000 points</li>
                        <li>Bonus de vitesse : jusqu'à 3000 points (si terminé en moins de 5 minutes)</li>
                        <li>Pénalité de coups : -50 points par coup supplémentaire au minimum requis</li>
                    </ul>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <a href="index.php" class="btn btn-primary">🎮 Jouer maintenant</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>