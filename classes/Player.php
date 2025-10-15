<?php

class Player {
    private $db;
    private $id;
    private $username;
    private $email;
    
    public function __construct(Database $db) {
        $this->db = $db;
    }
    
    public function login($username) {
        $player = $this->db->getPlayer($username);
        if ($player) {
            $this->id = $player['id'];
            $this->username = $player['username'];
            $this->email = $player['email'];
            return true;
        }
        return false;
    }
    
    public function register($username, $email = null) {
        $playerId = $this->db->createPlayer($username, $email);
        if ($playerId) {
            $this->id = $playerId;
            $this->username = $username;
            $this->email = $email;
            return true;
        }
        return false;
    }
    
    public function saveGameScore($pairs, $moves, $time, $score) {
        if (!$this->id) {
            return false;
        }
        return $this->db->saveScore($this->id, $pairs, $moves, $time, $score);
    }
    
    public function getStats() {
        if (!$this->id) {
            return null;
        }
        
        $stats = $this->db->getPlayerStats($this->id);
        $ranking = $this->db->getPlayerRanking($this->id);
        
        return array_merge($stats, ['ranking' => $ranking]);
    }
    
    public function getRecentScores($limit = 20) {
        if (!$this->id) {
            return [];
        }
        return $this->db->getPlayerScores($this->id, $limit);
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function getUsername() {
        return $this->username;
    }
    
    public function getEmail() {
        return $this->email;
    }
    
    public function isLoggedIn() {
        return !empty($this->id);
    }
}