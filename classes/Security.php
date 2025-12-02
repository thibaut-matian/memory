<?php

class Security {
    
    /**
     * Génère un token CSRF unique
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Valide le token CSRF
     */
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Sanitise les entrées utilisateur
     */
    public static function sanitize($data, $type = 'string') {
        if (is_array($data)) {
            return array_map(function($item) use ($type) {
                return self::sanitize($item, $type);
            }, $data);
        }
        
        $data = trim($data);
        
        switch ($type) {
            case 'string':
                return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            case 'int':
                return (int) filter_var($data, FILTER_SANITIZE_NUMBER_INT);
            case 'email':
                return filter_var($data, FILTER_SANITIZE_EMAIL);
            case 'username':
                return preg_replace('/[^a-zA-Z0-9_-]/', '', $data);
            default:
                return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
    
    /**
     * Rate limiting basique
     */
    public static function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 900) {
        $key = 'rate_limit_' . $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $attempts = $_SESSION[$key] ?? ['count' => 0, 'time' => time()];
        
        // Reset si window expirée
        if (time() - $attempts['time'] > $timeWindow) {
            $attempts = ['count' => 0, 'time' => time()];
        }
        
        if ($attempts['count'] >= $maxAttempts) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Enregistre une tentative
     */
    public static function recordAttempt($action) {
        $key = 'rate_limit_' . $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $attempts = $_SESSION[$key] ?? ['count' => 0, 'time' => time()];
        $attempts['count']++;
        $attempts['time'] = time();
        $_SESSION[$key] = $attempts;
    }
    
    /**
     * Validation avancée
     */
    public static function validateUsername($username) {
        return preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $username);
    }
    
    public static function validatePassword($password) {
        // Au moins 8 caractères, 1 majuscule, 1 chiffre
        return strlen($password) >= 8 && 
               preg_match('/[A-Z]/', $password) && 
               preg_match('/[0-9]/', $password);
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}