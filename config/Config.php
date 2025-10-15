<?php

// Configuration générale du jeu Memory
class Config {
    // Configuration de jeu
    const MIN_PAIRS = 3;
    const MAX_PAIRS = 12;
    const DEFAULT_PAIRS = 6;
    
    // Configuration des scores
    const BASE_SCORE_MULTIPLIER = 1000;
    const TIME_BONUS_MAX = 3000;
    const TIME_BONUS_THRESHOLD = 300; // 5 minutes
    const MOVE_PENALTY = 50;
    
    // Configuration du classement
    const LEADERBOARD_SIZE = 10;
    const HISTORY_SIZE = 20;
    
    // Configuration de la base de données
    const DB_PATH = __DIR__ . '/data/memory_game.db';
    
    // Messages d'erreur
    const ERROR_MESSAGES = [
        'username_required' => 'Nom d\'utilisateur requis',
        'username_taken' => 'Nom d\'utilisateur déjà utilisé',
        'user_not_found' => 'Utilisateur non trouvé',
        'invalid_action' => 'Action non reconnue',
        'no_game' => 'Aucun jeu en cours',
        'cannot_flip' => 'Impossible de retourner cette carte',
        'missing_data' => 'Données manquantes',
        'save_error' => 'Erreur lors de la sauvegarde',
        'server_error' => 'Erreur serveur'
    ];
    
    // Accomplissements
    const ACHIEVEMENTS = [
        'first_game' => [
            'icon' => '🎮',
            'title' => 'Premier Pas',
            'description' => 'Jouer sa première partie',
            'condition' => 'total_games >= 1'
        ],
        'ten_games' => [
            'icon' => '🔥',
            'title' => 'Joueur Régulier',
            'description' => 'Jouer 10 parties',
            'condition' => 'total_games >= 10'
        ],
        'hundred_games' => [
            'icon' => '💯',
            'title' => 'Centenaire',
            'description' => 'Jouer 100 parties',
            'condition' => 'total_games >= 100'
        ],
        'speed_demon' => [
            'icon' => '⚡',
            'title' => 'Démon de Vitesse',
            'description' => 'Terminer une partie en moins de 60 secondes',
            'condition' => 'best_time < 60'
        ],
        'perfectionist' => [
            'icon' => '🎯',
            'title' => 'Perfectionniste',
            'description' => 'Terminer une partie avec le minimum de coups',
            'condition' => 'best_moves <= pairs'
        ],
        'high_scorer' => [
            'icon' => '🏆',
            'title' => 'Gros Score',
            'description' => 'Obtenir un score de 5000 points',
            'condition' => 'best_score >= 5000'
        ],
        'top_ten' => [
            'icon' => '🥇',
            'title' => 'Top 10',
            'description' => 'Être dans le top 10',
            'condition' => 'ranking <= 10'
        ],
        'expert_level' => [
            'icon' => '🧠',
            'title' => 'Niveau Expert',
            'description' => 'Terminer une partie de 12 paires',
            'condition' => 'max_pairs_completed >= 12'
        ]
    ];
    
    // Symboles disponibles pour les cartes
    const CARD_SYMBOLS = [
        '🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼',
        '🐨', '🐯', '🦁', '🐸', '🐵', '🐔', '🐧', '🐦',
        '🦄', '🐝', '🦋', '🐌', '🐞', '🐜', '🦗', '🕷️',
        '🌸', '🌺', '🌻', '🌷', '🌹', '🌼', '🌿', '🍀',
        '⭐', '🌟', '✨', '💫', '🔥', '❄️', '☀️', '🌙'
    ];
    
    // Couleurs disponibles pour les cartes
    const CARD_COLORS = [
        '#e74c3c', '#3498db', '#2ecc71', '#f39c12', '#9b59b6',
        '#1abc9c', '#e67e22', '#34495e', '#ff6b6b', '#4ecdc4',
        '#45b7d1', '#96ceb4', '#ffeaa7', '#dda0dd', '#98d8c8',
        '#ff7675', '#6c5ce7', '#fd79a8', '#fdcb6e', '#55a3ff'
    ];
    
    /**
     * Obtenir un message d'erreur
     */
    public static function getErrorMessage($key, $default = 'Erreur inconnue') {
        return self::ERROR_MESSAGES[$key] ?? $default;
    }
    
    /**
     * Valider le nombre de paires
     */
    public static function validatePairs($pairs) {
        return max(self::MIN_PAIRS, min(self::MAX_PAIRS, (int)$pairs));
    }
    
    /**
     * Calculer le score d'un jeu
     */
    public static function calculateScore($pairs, $moves, $time) {
        $baseScore = $pairs * self::BASE_SCORE_MULTIPLIER;
        $timeBonus = max(0, self::TIME_BONUS_THRESHOLD - $time) * 10;
        $movePenalty = max(0, $moves - $pairs) * self::MOVE_PENALTY;
        
        return max(100, $baseScore + $timeBonus - $movePenalty);
    }
    
    /**
     * Obtenir les symboles de cartes
     */
    public static function getCardSymbols($count) {
        return array_slice(self::CARD_SYMBOLS, 0, $count);
    }
    
    /**
     * Obtenir les couleurs de cartes
     */
    public static function getCardColors($count) {
        return array_slice(self::CARD_COLORS, 0, $count);
    }
}