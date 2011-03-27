<?php
    
include './FourInALinePlayer.php';

class Player1 extends FourInALinePlayer
{
    public function getMove(array $grid) 
    {
        $column = -1;
        
        while (true) {
            $column = mt_rand(0, $this->width - 1);
            if (in_array(null, $grid[$column], true)) {
                break;
            }
        }
        return $column;
    }
    
    public function getName()
    {
        return "Random Rizer";
    }
}