<?php

require_once __DIR__ . '/../config/DatabaseConfig.php';

class Database {
    private $pdo;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try { 
            $this->pdo = new PDO(
                DatabaseConfig::getDSN(),
                DatabaseConfig::DB_USER,
                DatabaseConfig::DB_PASS,
                DatabaseConfig::getPDOOptions()
            );
        } catch(PDOException $e) {
            die("Erreur de connexion à la base de données MySQL: " . $e->getMessage() . 
                "<br><br><strong>Vérifiez que :</strong>" .
                "<ul>" .
                "<li>MySQL est démarré (via WAMP/XAMPP)</li>" .
                "<li>La base de données 'memory_game' existe</li>" .
                "<li>Les paramètres dans config/DatabaseConfig.php sont corrects</li>" .
                "</ul>" .
                "<a href='config/database.sql'>Voir le script SQL de création</a>");
        }
    }
    
    public function createPlayer($username, $email = null) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO players (username, email) VALUES (?, ?)");
            $stmt->execute([$username, $email]);
            return $this->pdo->lastInsertId();
        } catch(PDOException $e) {
            // Vérifier si c'est une erreur de duplicate
            if ($e->getCode() == 23000) {
                return false; // Username déjà existant
            }
            throw $e;
        }
    }
    
    public function getPlayer($username) {
        $stmt = $this->pdo->prepare("SELECT * FROM players WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
    
    public function saveScore($playerId, $pairs, $moves, $time, $score) {
        $stmt = $this->pdo->prepare("INSERT INTO scores (player_id, pairs, moves, time, score) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$playerId, $pairs, $moves, $time, $score]);
    }
    
    public function getTopScores($limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT p.username, s.pairs, s.moves, s.time, s.score, s.created_at
            FROM scores s
            INNER JOIN players p ON s.player_id = p.id
            ORDER BY s.score DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function getPlayerStats($playerId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_games,
                MAX(score) as best_score,
                ROUND(AVG(score)) as avg_score,
                MIN(time) as best_time,
                ROUND(AVG(time)) as avg_time,
                MIN(moves) as best_moves,
                ROUND(AVG(moves)) as avg_moves
            FROM scores 
            WHERE player_id = ?
        ");
        $stmt->execute([$playerId]);
        return $stmt->fetch();
    }
    
    public function getPlayerScores($playerId, $limit = 20) {
        $stmt = $this->pdo->prepare("
            SELECT pairs, moves, time, score, created_at
            FROM scores 
            WHERE player_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$playerId, $limit]);
        return $stmt->fetchAll();
    }
    
    public function getPlayerRanking($playerId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(DISTINCT p2.id) + 1 as ranking
            FROM players p1
            LEFT JOIN scores s1 ON p1.id = s1.player_id
            LEFT JOIN (
                SELECT player_id, MAX(score) as max_score
                FROM scores
                GROUP BY player_id
            ) best_scores ON p1.id = best_scores.player_id
            LEFT JOIN players p2 ON p2.id != p1.id
            LEFT JOIN (
                SELECT player_id, MAX(score) as max_score
                FROM scores
                GROUP BY player_id
            ) other_best ON p2.id = other_best.player_id
            WHERE p1.id = ? 
            AND (other_best.max_score > best_scores.max_score OR other_best.max_score IS NULL AND best_scores.max_score IS NOT NULL)
        ");
        $stmt->execute([$playerId]);
        $result = $stmt->fetch();
        return $result['ranking'] ?? 1;
    }
    
    /**
     * Méthode utilitaire pour obtenir des statistiques globales
     */
    public function getGlobalStats() {
        $stmt = $this->pdo->query("
            SELECT 
                COUNT(DISTINCT p.id) as total_players,
                COUNT(s.id) as total_games,
                MAX(s.score) as highest_score,
                ROUND(AVG(s.score)) as avg_score
            FROM players p
            LEFT JOIN scores s ON p.id = s.player_id
        ");
        return $stmt->fetch();
    }
    
    /**
     * Méthode pour vider les tables (utile pour les tests)
     */
    public function clearAllData() {
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $this->pdo->exec("TRUNCATE TABLE scores");
        $this->pdo->exec("TRUNCATE TABLE players");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
}