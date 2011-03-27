<?php

// some code reused from iBuildings challenge: http://www.ibuildings.com/challenge/

class ConnectFourAgainstServer
{
    protected $_argv;

    protected $_player1FileName;
    protected $_player1ClassName;

    protected $_player2Name;
    protected $_enemyId;
    protected $_enemyName;

    /**
     * @var FourInALinePlayer
     */
    protected $_player;
    protected $_playerNumber;

    protected $_width;
    protected $_height;

    protected $_msgArray;
    protected $_httpClient;

    /**
     * Constructor
     *
     * @param array $argv commandline arguments
     */
    public function __construct($argv)
    {
        //system('command /C cls');
        echo "Starting ConnectFourAgainstServer...\n";
        echo "==============================\n";

        $this->_argv = $argv;
        include './HttpClient.php';
        $this->_httpClient = new HttpClient();
    }

    /**
     * Check commandline arguments
     *
     * @return boolean $readyToStart true if the game can begin
     */
    public function processArguments()
    {
        $readyToStart = true;

        // check player 1 class
        if (!isset($this->_argv[1])) {
            $this->_msgArray[] = "Player 1 filename not set";
            $readyToStart = false;
        }
        else {
            $player1FileName = $this->_argv[1];

            if (!file_exists($player1FileName)){
                $this->_msgArray[] = "Player 1 file '{$player1FileName}' not found";
                $readyToStart = false;
            }
            else {
                $this->_player1FileName = $player1FileName;
                $this->_player1ClassName = $this->getClassNameFromPlayerFile($player1FileName);
            }
        }

        // check width (defaults to 7)
        if (!isset($this->_argv[2]) || !is_numeric($this->_argv[2]) || $this->_argv[2] < 5 || $this->_argv[2] > 20) {
            $this->_width = 7;
        }
        else {
            $this->_width = $this->_argv[2];
        }

        // check height (defaults to 6)
        if (!isset($this->_argv[3]) || !is_numeric($this->_argv[3]) || $this->_argv[3] < 4 || $this->_argv[3] > 12) {
            $this->_height = 6;
        }
        else {
            $this->_height = $this->_argv[3];
        }

        // check enemy
        $allAvailableEnemies = $this->_httpClient->getAllAvailableEnemies();
        if (!isset($this->_argv[4]) || !is_numeric($this->_argv[4]) || !isset($allAvailableEnemies[$this->_argv[4]])) {
        // if there is no enemyId, we will display all available enemies to the user and ask
            do {
                fwrite(STDOUT, "\nPlease choose your enemy: \n\n");

                foreach ($allAvailableEnemies as $enemyId => $enemyName) {
                    fwrite(STDOUT, $enemyId." ".$enemyName."\n");
                };

                fwrite(STDOUT, "\nEnemyId: ");

                // get input
                $enemyId = trim(fgets(STDIN));
            } while (!isset($allAvailableEnemies[$enemyId]));
            $this->_enemyId = $enemyId;
        } else {
            $this->_enemyId = $this->_argv[4];
        }
        $this->_enemyName = $allAvailableEnemies[$this->_enemyId];

        return $readyToStart;
    }

    /**
     * Create player and start the game
     */
    public function startGame()
    {
        // we need to create a temporary player object to get the name
        include './' . $this->_player1FileName;
        $playerTemp = new $this->_player1ClassName(1, $this->_width, $this->_height);

        // create a new game on the server
        $this->_playerNumber = $this->_httpClient->createNewGame($playerTemp->getName(), $this->_enemyId, $this->_width, $this->_height);

        // create player object
        $this->_player = new $this->_player1ClassName($this->_playerNumber, $this->_width, $this->_height);

        echo "\nStarting a game on a {$this->_width} x {$this->_height} grid (official grid is 7x6)\n";
        if ($this->_playerNumber == 1) {
            echo "Local player '" . $this->_player->getName() . "' (X) and remote player '" . $this->_enemyName . "' (O)\n";
        } else {
            echo "Remote player '" . $this->_enemyName . "' (X) and local player '" . $this->_player->getName() . "' (O)\n";
        }

        $this->_playGame();

        echo "Game over.\n";
    }

    protected function _playGame()
    {
        while(true) {
            $instruction = $this->_httpClient->getInstruction();
            switch ($instruction['instruction']) {
                case 'MOVE':
                    $this->_printGrid($instruction['grid']);
                    $column = $this->_player->getMove($instruction['grid']);
                    $response = $this->_httpClient->move($column);
                    $this->_printGrid($response['grid']);
                    break;
                case 'END':
                    if ($this->_playerNumber == $instruction['WinnerNumber']) {
                        echo "You won!\n";
                    } else {
                        echo "The enemy won!\n";
                    }
                    $this->_printGrid($instruction['grid']);
                    break 2;
                case 'NOOP':
                    sleep(2);
                    break;
                default: throw new Exception('Invalid instruction from server: '.var_export($instruction, true));
            }
        }
    }

    protected function _printGrid($grid)
    {
        for ($i = ($this->_height-1); $i >= 0; $i--) {
            echo "\n    ";
            for ($j = 0; $j < $this->_width; $j++) {
                switch ($grid[$j][$i]) {
                    case 1:
                        echo 'X';
                        break;
                    case 2:
                        echo 'O';
                        break;
                    default:
                        echo '.';
                        break;
                }
                echo '';
            }
        }
        echo "\n\n";
    }

    /**
     * Show messages
     *
     * @return boolean $return false in case of an error
     */
    public function showMessages()
    {
        if (empty($this->_msgArray)) {
            return true;
        }

        foreach ($this->_msgArray as $message) {
            echo " * $message \n";
        }

        echo "\nGame not started.\n";

        echo "\nUsage:\n";
        echo " php startConnectFourAgainstServer.php Player-Filename [width] [height] [enemyId]\n";
        
        return false;
    }

    /**
     * Uses the first occurance by default
     *
     * @param string $playerFile
     */
    protected function getClassNameFromPlayerFile($playerFile)
    {
        $tokens = token_get_all( file_get_contents($playerFile) );
        $class_token = false;

        foreach ($tokens as $token) {
            if ( !is_array($token) ) {
                continue;
            }

            if ($token[0] == T_CLASS) {
                $class_token = true;
            }
            else if ($class_token && $token[0] == T_STRING) {
                echo "Found player class '{$token[1]}' in file '{$playerFile}'.\n";
                return $token[1];
            }
        }
    }
}



////////////////////////////////////
// PROCESS ARGUMENTS AND RUN GAME //
////////////////////////////////////

error_reporting(E_ALL^E_NOTICE);


$connectFourAgainstServer = new ConnectFourAgainstServer($argv);
$readyToStart = $connectFourAgainstServer->processArguments();
$connectFourAgainstServer->showMessages();

if ($readyToStart) {
    $connectFourAgainstServer->startGame();
}