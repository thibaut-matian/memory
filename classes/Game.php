<?php

class Game {
    private $cards = [];
    private $flipped_cards = [];
    private $found_pairs = [];
    private $moves = 0;
    private $pairs_count;
    private $start_time;
    private $player_id;
    
    public function __construct($pairs_count, $player_id) {
        $this->pairs_count = $pairs_count;
        $this->player_id = $player_id;
        $this->start_time = time();
        $this->generateCards();
    }
    
    private function generateCards() {
        // Liste d'images One Piece (fichiers fournis dans assets/img/onepiece/)
        $images = [
            'Monkey_D_Luffy.png',
            'Kaidou_Anime_Infobox.webp',
            'Newgate.webp',
            'Marshall_D._Teach_Anime_Post_Ellipse_Infobox.webp',
            'ki_zaru_borsalino_5278.webp',
            'Roronoa_Zoro.jpg',
            'Sanji.webp',
            'Nami_face.jpg',
            'Nico_Robin_3Fe_de_27_ans.webp',
            'boa.webp',
            'kuma.webp',
            'Rocks_D._Xebec_Portrait.webp'
        ];
        $colors = ['#e74c3c', '#3498db', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#e67e22', '#34495e', '#f1c40f', '#95a5a6', '#d35400', '#27ae60'];
        
        // chemin relatif vers le dossier images (ajustez si besoin)
        $imgBase = 'assets/img/onepiece/';

        // Prendre seulement le nombre d'images nécessaires
        $selected_images = array_slice($images, 0, $this->pairs_count);
        $selected_colors = array_slice($colors, 0, $this->pairs_count);
        
        // Créer les paires avec les mêmes images et couleurs
        $card_data = [];
        for ($i = 0; $i < $this->pairs_count; $i++) {
            $imagePath = $imgBase . $selected_images[$i];
            $card_data[] = ['symbol' => $imagePath, 'color' => $selected_colors[$i]];
            $card_data[] = ['symbol' => $imagePath, 'color' => $selected_colors[$i]];
        }
        
        shuffle($card_data);
        
        // Créer les objets Card
        require_once __DIR__ . '/Card.php';
        $this->cards = [];
        foreach ($card_data as $index => $data) {
            // On passe le chemin d'image comme "symbol" : comparaison fonctionne toujours
            $this->cards[$index] = new Card($index, $data['symbol'], $data['color']);
        }
    }
    
    public function getCards() {
        $cards_array = [];
        foreach ($this->cards as $card) {
            $cards_array[] = [
                'id' => $card->getId(),
                // 'symbol' contient maintenant le chemin de l'image ; on expose aussi 'image'
                'symbol' => $card->getSymbol(),
                'image' => $card->getSymbol(),
                'color' => $card->getColor(),
                'isFlipped' => in_array($card->getId(), $this->flipped_cards),
                'isMatched' => in_array($card->getId(), $this->found_pairs)
            ];
        }
        return $cards_array;
    }
    
    public function flipCard($card_id) {
        if (count($this->flipped_cards) >= 2) {
            return false; // Déjà 2 cartes retournées
        }
        
        if (in_array($card_id, $this->flipped_cards) || 
            in_array($card_id, $this->found_pairs)) {
            return false; // Carte déjà retournée ou trouvée
        }
        
        $this->cards[$card_id]->flip();
        $this->flipped_cards[] = $card_id;
        
        // Si 2 cartes retournées, vérifier si c'est une paire
        if (count($this->flipped_cards) == 2) {
            $this->moves++;
            
            $card1 = $this->cards[$this->flipped_cards[0]];
            $card2 = $this->cards[$this->flipped_cards[1]];
            
            if ($card1->getSymbol() === $card2->getSymbol()) {
                // Paire trouvée !
                $this->found_pairs = array_merge($this->found_pairs, $this->flipped_cards);
                // Marquer les cartes comme appariées si la méthode existe
                if (method_exists($card1, 'setMatched')) { $card1->setMatched(); }
                if (method_exists($card2, 'setMatched')) { $card2->setMatched(); }
                $this->flipped_cards = [];
                return 'pair_found';
            }
            // Pas une paire: indiquer l'absence de paire (la remise à zéro sera gérée à l'appelant)
            return 'no_pair';
        }
        
        return true;
    }
    
    public function continueGame() {
        // Remettre les cartes face cachée si ce n'est pas une paire
        if (count($this->flipped_cards) == 2) {
            foreach ($this->flipped_cards as $card_id) {
                if (!in_array($card_id, $this->found_pairs)) {
                    $this->cards[$card_id]->flip(); // Remettre face cachée
                }
            }
            $this->flipped_cards = [];
        }
    }
    
    public function isGameOver() {
        return count($this->found_pairs) === ($this->pairs_count * 2);
    }
    
    public function canFlipCard() {
        return count($this->flipped_cards) < 2;
    }
    
    public function getFlippedCards() {
        return $this->flipped_cards;
    }
    
    public function getFoundPairs() {
        return $this->found_pairs;
    }
    
    public function getMoves() {
        return $this->moves;
    }
    
    public function getElapsedTime() {
        return time() - $this->start_time;
    }
    
    public function getPairsCount() {
        return $this->pairs_count;
    }
    
    public function getFoundPairsCount() {
        return count($this->found_pairs) / 2;
    }
    
    public function calculateScore() {
        $elapsed_time = $this->getElapsedTime();
        $base_score = $this->pairs_count * 1000;
        
        // Bonus de vitesse (max 3000 points)
        $time_bonus = max(0, 3000 - ($elapsed_time * 10));
        
        // Pénalité pour les coups supplémentaires
        $optimal_moves = $this->pairs_count;
        $extra_moves = max(0, $this->moves - $optimal_moves);
        $move_penalty = $extra_moves * 50;
        
        $total_score = $base_score + $time_bonus - $move_penalty;
        return max(100, $total_score); // Score minimum de 100
    }
    
    public function saveScore() {
        if ($this->isGameOver()) {
            $db = Database::getInstance();
            $score = $this->calculateScore();
            $elapsed_time = $this->getElapsedTime();
            
            $stmt = $db->prepare("
                INSERT INTO scores (player_id, score, moves, time_seconds, pairs_count, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            return $stmt->execute([
                $this->player_id,
                $score,
                $this->moves,
                $elapsed_time,
                $this->pairs_count
            ]);
        }
        return false;
    }
    
    /**
     * Retourne toutes les statistiques du jeu
     */
    public function getStats() {
        return [
            'pairs' => $this->pairs_count,
            'moves' => $this->moves,
            'flippedCards' => count($this->flipped_cards),
            'foundPairs' => count($this->found_pairs),
            'score' => $this->calculateScore(),
            'elapsed_time' => $this->getElapsedTime(),
            'isCompleted' => $this->isGameOver()
        ];
    }
    
    /**
     * Réinitialise les cartes retournées
     */
    public function resetFlippedCards() {
        // Ne remettre que les deux cartes récemment retournées si elles ne sont pas une paire
        $toReset = $this->flipped_cards;
        $this->flipped_cards = [];
        foreach ($toReset as $card_id) {
            if (!in_array($card_id, $this->found_pairs)) {
                // Assurer que la carte revient face cachée
                if (method_exists($this->cards[$card_id], 'isFlipped') && $this->cards[$card_id]->isFlipped()) {
                    $this->cards[$card_id]->flip();
                }
            }
        }
    }
}
?>