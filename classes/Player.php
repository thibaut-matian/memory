<?php

class Player {
    private $db;
    private $id;
    private $username;
    private $email;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function register($username, $password, $email = null) {
        if (strlen($username) < 3 || strlen($username) > 20) {
            throw new Exception("Le nom d'utilisateur doit contenir entre 3 et 20 caractères");
        }
        
        if (strlen($password) < 8) {
            throw new Exception("Le mot de passe doit contenir au moins 8 caractères");
        }
        
        $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);
        
        try {
            $playerId = $this->db->createPlayer($username, $hashedPassword, $email);
            if ($playerId) {
                $this->id = $playerId;
                $this->username = $username;
                $this->email = $email;
                return true;
            }
            return false;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de l'inscription: " . $e->getMessage());
        }
    }
    
    public function login($username, $password) {
        try {
            $playerData = $this->db->getPlayer($username);
            
            // ✅ VÉRIFICATION AVANT password_verify()
            if ($playerData) {
                // Vérifier que le hash existe
                if (empty($playerData['password'])) {
                    // Ancien compte sans mot de passe → forcer la mise à jour
                    return false; // ou throw new Exception("Compte non sécurisé, veuillez vous réinscrire");
                }
                
                // Vérification du mot de passe
                if (password_verify($password, $playerData['password'])) {
                    $this->id = $playerData['id'];
                    $this->username = $playerData['username'];
                    $this->email = $playerData['email'] ?? null;
                    return true;
                }
            }
            
            return false;
        } catch (Exception $e) {
            throw new Exception("Erreur de connexion: " . $e->getMessage());
        }
    }
    
    public function getId() { return $this->id; }
    public function getUsername() { return $this->username; }
    public function getEmail() { return $this->email; }
    
    public function saveGameScore($pairs, $moves, $time, $score) {
        if (!$this->id) {
            throw new Exception("Joueur non connecté");
        }
        return $this->db->saveScore($this->id, $pairs, $moves, $time, $score);
    }
}