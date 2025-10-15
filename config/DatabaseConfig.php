<?php

/**
 * Configuration de la base de données MySQL
 */
class DatabaseConfig {
    // Configuration MySQL - À modifier selon votre configuration
    const DB_HOST = 'localhost';
    const DB_NAME = 'memory_game';
    const DB_USER = 'root';
    const DB_PASS = '';  // Mot de passe par défaut WAMP (vide)
    const DB_PORT = 3306;
    const DB_CHARSET = 'utf8mb4';
    
    /**
     * Obtenir la chaîne de connexion DSN
     */
    public static function getDSN() {
        return sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            self::DB_HOST,
            self::DB_PORT,
            self::DB_NAME,
            self::DB_CHARSET
        );
    }
    
    /**
     * Obtenir les options PDO recommandées
     */
    public static function getPDOOptions() {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . self::DB_CHARSET
        ];
    }
    
    /**
     * Tester la connexion à la base de données
     */
    public static function testConnection() {
        try {
            $pdo = new PDO(
                self::getDSN(),
                self::DB_USER,
                self::DB_PASS,
                self::getPDOOptions()
            );
            return ['success' => true, 'message' => 'Connexion MySQL réussie'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur MySQL: ' . $e->getMessage()];
        }
    }
}