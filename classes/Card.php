<?php

class Card {
    private $id;
    private $symbol;
    private $isFlipped;
    private $isMatched;
    private $color;
    
    public function __construct($id, $symbol, $color = '#3498db') {
        $this->id = $id;
        $this->symbol = $symbol;
        $this->isFlipped = false;
        $this->isMatched = false;
        $this->color = $color;
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function getSymbol() {
        return $this->symbol;
    }
    
    public function isFlipped() {
        return $this->isFlipped;
    }
    
    public function isMatched() {
        return $this->isMatched;
    }
    
    public function getColor() {
        return $this->color;
    }
    
    public function flip() {
        if (!$this->isMatched) {
            $this->isFlipped = !$this->isFlipped;
        }
    }
    
    public function setMatched() {
        $this->isMatched = true;
        $this->isFlipped = true;
    }
    
    public function reset() {
        $this->isFlipped = false;
        $this->isMatched = false;
    }
    
    public function toArray() {
        return [
            'id' => $this->id,
            'symbol' => $this->symbol,
            'isFlipped' => $this->isFlipped,
            'isMatched' => $this->isMatched,
            'color' => $this->color
        ];
    }
}