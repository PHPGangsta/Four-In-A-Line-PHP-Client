<?php

if (!class_exists("FourInALinePlayer")) {
    abstract class FourInALinePlayer {

        const PLAYER_ONE = 1;

        const PLAYER_TWO = 2;

        /**
         * Current player, either FourInALinePlayer::PLAYER_ONE or FourInALinePlayer::PLAYER_TWO
         */
        protected $currentPlayer;

        /**
         * Number of columns in the grid
         */
        protected $width;

        /**
         * Number of rows in the grid
         */
        protected $height;

        /**
         * Constructs a new Four in a Line player. This is called before the game begins.
         *
         * @param integer $player  The current player, either FourInALinePlayer::PLAYER_ONE or FourInALinePlayer::PLAYER_TWO
         * @param integer $width   The amount of columns in the game area
         * @param integer $height  The amount of rows in the game area
         */
        final public function __construct ($currentPlayer, $width, $height) {
           $this->currentPlayer = $currentPlayer;
           $this->width         = $width;
           $this->height        = $height;
        }

        /**
         * Get the next move from the player. The player must return the column 
         * to which the next disc is dropped. Each one of the slots in the grid 
         * contain either FourInALinePlayer::PLAYER_ONE, FourInALinePlayer::PLAYER_TWO or null
         *
         * @param array $grid  The current state of the grid as an array
         * @return integer Returns the column number where to drop the disc
         */
        abstract public function getMove (array $grid);

        /**
         * Returns the players name.
         * @return string
         */
        abstract public function getName ();
    }
}
else {
    // class already included
}